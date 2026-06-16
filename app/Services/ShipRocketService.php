<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ShipRocketService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('shiprocket.base_url');
    }

    protected function authenticate()
    {
        $response = Http::post($this->baseUrl . 'auth/login', [
            'email' => config('shiprocket.email'),
            'password' => config('shiprocket.password'),
        ]);

        if ($response->successful()) {
            $this->token = $response->json()['token'];
            return true;
        }

        throw new \Exception('ShipRocket authentication failed: ' . $response->body());
    }

    protected function getHeaders()
    {
        if (!$this->token) {
            $this->authenticate();
        }

        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
        ];
    }

    public function createOrder($order, $packageData)
    {
        $user = $order->user;
        $addressParts = explode(',', $order->shipping_address);
        $city = !empty($addressParts[1]) ? trim($addressParts[1]) : 'City';
        $state = !empty($addressParts[2]) ? trim($addressParts[2]) : 'State';
        $pincode = '';

        preg_match('/\b\d{6}\b/', $order->shipping_address, $matches);
        if (!empty($matches[0])) {
            $pincode = $matches[0];
        }

        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'name' => $item->product_name,
                'sku' => str_replace(' ', '-', strtolower($item->product_name)) . '-' . $item->id,
                'units' => $item->quantity,
                'selling_price' => $item->price,
            ];
        }

        $pincode = !empty($pincode) ? $pincode : '110001';

        $payload = [
            'order_id' => $order->order_number,
            'order_date' => $order->created_at->format('Y-m-d H:i:s'),
            'billing_customer_name' => $user->full_name ?? $user->first_name . ' ' . $user->last_name,
            'billing_last_name' => $user->last_name ?? '',
            'billing_address' => trim($addressParts[0]),
            'billing_city' => $city,
            'billing_pincode' => $pincode,
            'billing_state' => $state,
            'billing_country' => 'India',
            'billing_email' => $user->email,
            'billing_phone' => $user->phone ?? '9999999999',
            'shipping_is_billing' => true,
            'shipping_first_name' => $user->full_name ?? $user->first_name . ' ' . $user->last_name,
            'shipping_last_name' => $user->last_name ?? '',
            'shipping_address' => trim($addressParts[0]),
            'shipping_city' => $city,
            'shipping_pincode' => $pincode,
            'shipping_state' => $state,
            'shipping_country' => 'India',
            'shipping_email' => $user->email,
            'shipping_phone' => $user->phone ?? '9999999999',
            'order_items' => $items,
            'payment_method' => $order->payment_status === 'paid' ? 'Prepaid' : 'COD',
            'sub_total' => $order->total_amount,
            'length' => $packageData['length'],
            'breadth' => $packageData['breadth'],
            'height' => $packageData['height'],
            'weight' => $packageData['weight'],
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post($this->baseUrl . 'orders/create/adhoc', $payload);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('ShipRocket order creation failed: ' . $response->body());
    }

    public function checkCourierServiceability($pickupPincode, $deliveryPincode, $weight)
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get($this->baseUrl . 'courier/serviceability', [
                'pickup_postcode' => $pickupPincode,
                'delivery_postcode' => $deliveryPincode,
                'weight' => $weight,
                'cod' => 0,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $recommendedId = $data['data']['recommended_courier_company_id'] ?? $data['recommended_courier_company_id'] ?? null;
            $courierName = $data['data']['recommended_courier_company_name'] ?? $data['recommended_courier_company_name'] ?? null;
            $couriers = $data['data']['available_courier_companies'] ?? $data['available_courier_companies'] ?? [];

            return [
                'recommended_courier_id' => $recommendedId,
                'recommended_courier_name' => $courierName,
                'available_couriers' => $couriers,
                'raw' => $data,
            ];
        }

        $errorBody = $response->body();
        return ['error' => $errorBody, 'status' => $response->status(), 'raw' => json_decode($errorBody, true)];
    }

    public function generateAWB($shipmentId, $courierId)
    {
        $payload = [
            'shipment_id' => [(int) $shipmentId],
            'courier_id' => (int) $courierId,
        ];

        $response = Http::withHeaders($this->getHeaders())
            ->post($this->baseUrl . 'courier/assign/awb', $payload);

        if ($response->successful()) {
            return $response->json();
        }

        $errorBody = $response->body();
        $decoded = json_decode($errorBody, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['message'])) {
            throw new \Exception('ShipRocket AWB error: ' . $decoded['message']);
        }

        return ['error' => $errorBody, 'status' => $response->status()];
    }

    public function cancelOrder($orderNumber)
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post($this->baseUrl . 'orders/cancel', [
                'order_id' => [$orderNumber],
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('ShipRocket order cancellation failed: ' . $response->body());
    }

    public function generatePickup($shipmentIds)
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post($this->baseUrl . 'courier/generate/pickup', [
                'shipment_id' => $shipmentIds,
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('ShipRocket pickup generation failed: ' . $response->body());
    }

    public function generateLabel($shipmentId)
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post($this->baseUrl . 'courier/generate/label', [
                'shipment_id' => [$shipmentId],
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('ShipRocket label error: ' . $response->body());
    }

    public function generateInvoice($orderId)
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post($this->baseUrl . 'courier/generate/invoice', [
                'order_id' => [$orderId],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['invoice_url'])) {
                return $data;
            }
        }

        throw new \Exception('ShipRocket invoice error: ' . $response->body());
    }

    public function trackShipment($shipmentId = null, $awbCode = null, $orderId = null)
    {
        $params = [];
        if ($shipmentId) {
            $params['shipment_id'] = $shipmentId;
        } elseif ($awbCode) {
            $params['awb_code'] = $awbCode;
        } elseif ($orderId) {
            $params['order_id'] = $orderId;
        }

        $response = Http::withHeaders($this->getHeaders())
            ->get($this->baseUrl . 'shipments/track', $params);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('ShipRocket tracking error: ' . $response->body());
    }
}
