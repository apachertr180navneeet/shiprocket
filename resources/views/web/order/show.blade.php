@extends('web.layouts.app')
@section('content')
<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Order #{{ $order->order_number }}</h2>
                    <div>
                        <a href="{{ route('web.orders.edit', $order->id) }}" class="btn btn-warning">Edit</a>
                        <a href="{{ route('web.orders.index') }}" class="btn btn-secondary">Back</a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-3">
                            <div class="card-header fw-semibold">Order Details</div>
                            <div class="card-body">
                                <p><strong>Order#:</strong> {{ $order->order_number }}</p>
                                <p><strong>User:</strong> {{ $order->user->full_name ?? 'N/A' }} ({{ $order->user->email ?? '' }})</p>
                                <p><strong>Status:</strong>
                                    <span class="badge bg-{{ $order->status == 'completed' ? 'success' : ($order->status == 'cancelled' ? 'danger' : ($order->status == 'processing' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </p>
                                <p><strong>Payment:</strong>
                                    <span class="badge bg-{{ $order->payment_status == 'paid' ? 'success' : ($order->payment_status == 'refunded' ? 'info' : 'secondary') }}">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </p>
                                <p><strong>Total:</strong> ${{ number_format($order->total_amount, 2) }}</p>
                                <p><strong>Date:</strong> {{ $order->created_at->format('d M Y h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-3">
                            <div class="card-header fw-semibold">Shipping</div>
                            <div class="card-body">
                                <p class="mb-0">{{ $order->shipping_address }}</p>
                                @if($order->notes)
                                    <hr><p class="mb-0"><strong>Notes:</strong> {{ $order->notes }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="card shadow-sm mb-3">
                            <div class="card-header fw-semibold">Update Status</div>
                            <div class="card-body">
                                <form action="{{ route('web.orders.updateStatus', $order->id) }}" method="POST">
                                    @csrf @method('PUT')
                                    <div class="input-group">
                                        <select name="status" class="form-select">
                                            <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                            <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                            <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">Order Items</div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>${{ number_format($item->price, 2) }}</td>
                                    <td>${{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="4" class="text-end">Grand Total</th>
                                    <th>${{ number_format($order->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
