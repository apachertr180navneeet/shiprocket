@extends('web.layouts.app')
@section('content')
<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Edit Order #{{ $order->order_number }}</h2>
                </div>

                <form action="{{ route('web.orders.update', $order->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">User</label>
                                <select class="form-select" disabled>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $order->user_id == $user->id ? 'selected' : '' }}>{{ $user->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Shipping Address</label>
                                <textarea name="shipping_address" class="form-control" rows="2" required>{{ $order->shipping_address }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2">{{ $order->notes }}</textarea>
                            </div>

                            <hr>
                            <h5 class="fw-semibold">Order Items</h5>
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    <tr>
                                        <td>{{ $item->product_name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>₹{{ number_format($item->price, 2) }}</td>
                                        <td>₹{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <hr>
                            <button type="submit" class="btn btn-primary">Update Order</button>
                            <a href="{{ route('web.orders.show', $order->id) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
