<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
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
        $users = User::where('role', 'user')->where('status', 'active')->get();
        return view('web.order.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $request->validate([
            'user_id' => 'required|exists:users,id',
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

            $order = Order::create([
                'user_id' => $data['user_id'],
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

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate(['status' => 'required|in:pending,processing,completed,cancelled']);

        $order->status = $request->status;
        $order->save();

        return redirect()->route('web.orders.index')->with('success', 'Order status updated successfully');
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->status = 'cancelled';
        $order->save();

        return redirect()->route('web.orders.index')->with('success', 'Order cancelled successfully');
    }
}
