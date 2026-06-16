<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\ShipRocketService;
use DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user', 'items')->orderBy('id', 'desc')->get();
        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with('user', 'items')->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate(['status' => 'required|in:pending,processing,completed,cancelled']);
        $newStatus = $request->status;

        if ($newStatus === 'cancelled' && ($order->shiprocket_order_id || $order->awb_number)) {
            try {
                $shiprocket = app(ShipRocketService::class);
                $shiprocket->cancelOrder($order->order_number);
            } catch (\Exception $e) {
                return redirect()->route('admin.orders.show', $id)->with('error', 'ShipRocket cancellation failed: ' . $e->getMessage());
            }
        }

        $order->status = $newStatus;
        if ($newStatus === 'cancelled' && $order->payment_status === 'paid') {
            $order->payment_status = 'refunded';
        }
        $order->save();

        return redirect()->route('admin.orders.show', $id)->with('success', 'Order status updated successfully');
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate(['payment_status' => 'required|in:unpaid,paid,refunded']);
        $order->payment_status = $request->payment_status;
        $order->save();

        return redirect()->route('admin.orders.show', $id)->with('success', 'Payment status updated successfully');
    }

    public function addShipment(Request $request, $id, ShipRocketService $shiprocket)
    {
        $order = Order::findOrFail($id);

        if ($order->payment_status !== 'paid' || $order->status !== 'processing') {
            return redirect()->route('admin.orders.show', $id)->with('error', 'Shipment can only be created for paid & processing orders');
        }

        $request->validate([
            'weight' => 'required|numeric|min:0.1',
            'length' => 'required|numeric|min:1',
            'breadth' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
        ]);

        try {
            DB::beginTransaction();

            $result = $shiprocket->createOrder($order, $request->only(['weight', 'length', 'breadth', 'height']));

            $order->shiprocket_order_id = $result['order_id'] ?? null;
            $order->shiprocket_shipment_id = $result['shipment_id'] ?? null;
            $order->shipment_carrier = $result['courier_name'] ?? '';
            $order->shiprocket_response = json_encode($result);
            $order->shipped_at = now();

            $order->awb_number = $result['awb_code'] ?? $result['awb_assign'][0]['awb_code'] ?? '';

            if (empty($order->awb_number)) {
                $shipmentId = $result['shipment_id'] ?? null;
                if ($shipmentId) {
                    $pincode = '';
                    preg_match('/\b\d{6}\b/', $order->shipping_address, $matches);
                    $pincode = !empty($matches[0]) ? $matches[0] : '110001';

                    $courierData = $shiprocket->checkCourierServiceability('110001', $pincode, $request->weight);
                    $courierId = null;
                    if ($courierData && !empty($courierData['recommended_courier_id'])) {
                        $courierId = $courierData['recommended_courier_id'];
                    } elseif ($courierData && !empty($courierData['available_couriers'])) {
                        $courierId = $courierData['available_couriers'][0]['courier_company_id'] ?? null;
                    }

                    if ($courierId) {
                        try {
                            $awbResult = $shiprocket->generateAWB($shipmentId, $courierId);
                            if (is_array($awbResult) && (isset($awbResult['awb_code']) || isset($awbResult['awb_assign']))) {
                                $order->awb_number = $awbResult['awb_code'] ?? $awbResult['awb_assign'][0]['awb_code'] ?? $awbResult['data']['awb_code'] ?? '';
                                $order->shipment_carrier = $awbResult['courier_name'] ?? $awbResult['data']['courier_name'] ?? $order->shipment_carrier;
                                $existing = json_decode($order->shiprocket_response, true) ?? [];
                                $existing['awb_response'] = $awbResult;
                                $order->shiprocket_response = json_encode($existing);
                            } elseif (is_array($awbResult) && isset($awbResult['error'])) {
                                $existing = json_decode($order->shiprocket_response, true) ?? [];
                                $existing['awb_error'] = $awbResult['error'];
                                $existing['awb_note'] = 'AWB not generated. Manage from ShipRocket dashboard.';
                                $order->shiprocket_response = json_encode($existing);
                            }
                        } catch (\Exception $e) {
                            $existing = json_decode($order->shiprocket_response, true) ?? [];
                            $existing['awb_error'] = $e->getMessage();
                            $existing['awb_note'] = 'AWB generation failed. Manage from ShipRocket dashboard.';
                            $order->shiprocket_response = json_encode($existing);
                        }
                    } else {
                        $existing = json_decode($order->shiprocket_response, true) ?? [];
                        $existing['awb_note'] = 'No courier serviceable for this pincode. Assign AWB from ShipRocket dashboard.';
                        $order->shiprocket_response = json_encode($existing);
                    }
                }
            }

            $order->save();
            DB::commit();

            return redirect()->route('admin.orders.show', $id)->with('success', 'Shipment created successfully via ShipRocket.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('admin.orders.show', $id)->with('error', 'ShipRocket error: ' . $e->getMessage());
        }
    }

    public function schedulePickup($id, ShipRocketService $shiprocket)
    {
        $order = Order::findOrFail($id);

        if (!$order->shiprocket_shipment_id) {
            return redirect()->route('admin.orders.show', $id)->with('error', 'Create shipment first');
        }

        try {
            $result = $shiprocket->generatePickup([$order->shiprocket_shipment_id]);
            $existing = json_decode($order->shiprocket_response, true) ?? [];
            $existing['pickup_response'] = $result;
            $order->shiprocket_response = json_encode($existing);
            $order->save();

            return redirect()->route('admin.orders.show', $id)->with('success', 'Pickup scheduled successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.orders.show', $id)->with('error', 'Pickup failed: ' . $e->getMessage());
        }
    }

    public function generateLabel($id, ShipRocketService $shiprocket)
    {
        $order = Order::findOrFail($id);

        if (!$order->shiprocket_shipment_id) {
            return redirect()->route('admin.orders.show', $id)->with('error', 'Create shipment first');
        }

        try {
            $result = $shiprocket->generateLabel($order->shiprocket_shipment_id);
            if (isset($result['label_url'])) {
                $existing = json_decode($order->shiprocket_response, true) ?? [];
                $existing['label_url'] = $result['label_url'];
                $order->shiprocket_response = json_encode($existing);
                $order->save();
                return redirect($result['label_url']);
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.orders.show', $id)->with('error', 'Label: ' . $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $id)->with('error', 'Label generation failed');
    }

    public function generateInvoice($id, ShipRocketService $shiprocket)
    {
        $order = Order::findOrFail($id);

        if (!$order->shiprocket_order_id) {
            return redirect()->route('admin.orders.show', $id)->with('error', 'Create shipment first');
        }

        try {
            $result = $shiprocket->generateInvoice($order->shiprocket_order_id);
            if (isset($result['invoice_url'])) {
                $existing = json_decode($order->shiprocket_response, true) ?? [];
                $existing['invoice_url'] = $result['invoice_url'];
                $order->shiprocket_response = json_encode($existing);
                $order->save();
                return redirect($result['invoice_url']);
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.orders.show', $id)->with('error', 'Invoice: ' . $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $id)->with('error', 'Invoice generation failed');
    }

    public function track($id, ShipRocketService $shiprocket)
    {
        $order = Order::findOrFail($id);

        if (!$order->awb_number) {
            return redirect()->route('admin.orders.show', $id)->with('error', 'No AWB number to track');
        }

        try {
            $result = $shiprocket->trackShipment(null, $order->awb_number);
            return redirect()->route('admin.orders.show', $id)->with('tracking', $result);
        } catch (\Exception $e) {
            return redirect()->route('admin.orders.show', $id)->with('error', 'Track: ' . $e->getMessage());
        }
    }
}
