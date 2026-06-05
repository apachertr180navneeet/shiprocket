<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use DB;

class OrderController extends Controller
{
    public function index()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $orders = Order::where('user_id', $user->id)->with('items')->orderBy('id', 'desc')->get();
            return response()->json(['status' => true, 'message' => 'Orders fetched successfully', 'data' => $orders], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 200);
        }
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'shipping_address' => 'required|string',
            'notes' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 200);
        }

        try {
            DB::beginTransaction();
            $user = JWTAuth::parseToken()->authenticate();
            $order_number = 'ORD-' . strtoupper(uniqid());
            $total_amount = 0;

            foreach ($data['items'] as $item) {
                $total_amount += $item['price'] * $item['quantity'];
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
            $order->load('items');
            return response()->json(['status' => true, 'message' => 'Order created successfully', 'data' => $order], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 200);
        }
    }

    public function show($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $order = Order::where('user_id', $user->id)->with('items')->find($id);
            if (!$order) {
                return response()->json(['status' => false, 'message' => 'Order not found'], 200);
            }
            return response()->json(['status' => true, 'message' => 'Order detail fetched successfully', 'data' => $order], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 200);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $order = Order::where('user_id', $user->id)->find($id);
            if (!$order) {
                return response()->json(['status' => false, 'message' => 'Order not found'], 200);
            }
            if ($order->status !== 'pending') {
                return response()->json(['status' => false, 'message' => 'Only pending orders can be updated'], 200);
            }

            $data = $request->all();
            if (isset($data['shipping_address'])) {
                $order->shipping_address = $data['shipping_address'];
            }
            if (isset($data['notes'])) {
                $order->notes = $data['notes'];
            }
            $order->save();

            return response()->json(['status' => true, 'message' => 'Order updated successfully', 'data' => $order->load('items')], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 200);
        }
    }

    public function destroy($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $order = Order::where('user_id', $user->id)->find($id);
            if (!$order) {
                return response()->json(['status' => false, 'message' => 'Order not found'], 200);
            }
            if ($order->status !== 'pending') {
                return response()->json(['status' => false, 'message' => 'Only pending orders can be cancelled'], 200);
            }
            $order->status = 'cancelled';
            $order->save();

            return response()->json(['status' => true, 'message' => 'Order cancelled successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 200);
        }
    }
}
