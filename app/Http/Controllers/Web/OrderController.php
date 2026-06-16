<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\ShipRocketService;
use DB, Helper;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user', 'items')->orderBy('id', 'desc')->get();
        return view('web.order.index', compact('orders'));
    }

    public function create()
    {
        return view('web.order.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'sometimes|string',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'shipping_address' => 'required|string',
        ]);

        try {
            DB::beginTransaction();
            $order_number = 'ORD-' . strtoupper(uniqid());
            $total_amount = 0;

            foreach ($data['items'] as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }

            $user = User::where('email', $data['email'])->first();
            if (!$user) {
                $full_name = $data['first_name'] . ' ' . $data['last_name'];
                $user = User::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'full_name' => $full_name,
                    'slug' => Helper::slug('users', $full_name),
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? '',
                    'password' => bcrypt('password'),
                    'country_code' => '1',
                    'country' => 'India',
                    'address' => $data['shipping_address'],
                    'role' => 'user',
                    'status' => 'active',
                ]);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $order_number,
                'total_amount' => $total_amount,
                'shipping_address' => $data['shipping_address'],
                'notes' => $data['notes'] ?? '',
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ]);

            foreach ($data['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                ]);
            }

            DB::commit();
            return redirect()->route('web.orders.index')->with('success', 'Order created successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $order = Order::with('user', 'items')->findOrFail($id);
        return view('web.order.show', compact('order'));
    }

    public function edit($id)
    {
        $order = Order::with('items')->findOrFail($id);
        $users = User::where('role', 'user')->where('status', 'active')->get();
        return view('web.order.edit', compact('order', 'users'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'shipping_address' => 'required|string',
        ]);

        $order->shipping_address = $request->shipping_address;
        $order->notes = $request->notes ?? '';
        $order->save();

        return redirect()->route('web.orders.index')->with('success', 'Order updated successfully');
    }

    public function track($id)
    {
        $order = Order::with('user', 'items')->findOrFail($id);

        if (!$order->awb_number) {
            return response()->json(['error' => 'No tracking number available'], 404);
        }

        try {
            $shiprocket = app(ShipRocketService::class);
            $data = $shiprocket->trackShipment(null, $order->awb_number);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status === 'cancelled') {
            return redirect()->route('web.orders.show', $id)->with('error', 'Order is already cancelled');
        }

        if ($order->status === 'completed') {
            return redirect()->route('web.orders.show', $id)->with('error', 'Completed orders cannot be cancelled');
        }

        try {
            DB::beginTransaction();

            if ($order->shiprocket_order_id || $order->awb_number) {
                $shiprocket = app(ShipRocketService::class);
                $shiprocket->cancelOrder($order->order_number);
            }

            $order->status = 'cancelled';
            $order->payment_status = 'refunded';
            $order->save();
            DB::commit();

            return redirect()->route('web.orders.index')->with('success', 'Order cancelled and payment refunded');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('web.orders.show', $id)->with('error', 'Cancellation failed: ' . $e->getMessage());
        }
    }
}
