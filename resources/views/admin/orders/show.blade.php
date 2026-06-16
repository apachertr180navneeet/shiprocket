@extends('admin.layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Order #{{ $order->order_number }}</h4>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header fw-semibold">Order Details</div>
                <div class="card-body">
                    <p><strong>Order#:</strong> {{ $order->order_number }}</p>
                    <p><strong>Customer:</strong> {{ $order->user->full_name ?? 'N/A' }}</p>
                    <p><strong>Email:</strong> {{ $order->user->email ?? 'N/A' }}</p>
                    <p><strong>Phone:</strong> {{ $order->user->phone ?? 'N/A' }}</p>
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
                    <p><strong>Total:</strong> ₹{{ number_format($order->total_amount, 2) }}</p>
                    <p><strong>Date:</strong> {{ $order->created_at->format('d M Y h:i A') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header fw-semibold">Shipping Address</div>
                <div class="card-body">
                    <p>{{ $order->shipping_address }}</p>
                    @if($order->notes)
                        <hr><p><strong>Notes:</strong> {{ $order->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header fw-semibold">Update Order Status</div>
                <div class="card-body">
                    <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST">
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

            <div class="card mb-4">
                <div class="card-header fw-semibold">Update Payment Status</div>
                <div class="card-body">
                    <form action="{{ route('admin.orders.updatePaymentStatus', $order->id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="input-group">
                            <select name="payment_status" class="form-select">
                                <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="refunded" {{ $order->payment_status == 'refunded' ? 'selected' : '' }}>Refunded</option>
                            </select>
                            <button type="submit" class="btn btn-info">Update</button>
                        </div>
                    </form>
                </div>
            </div>

            @if($order->payment_status == 'paid' && $order->status == 'processing')
            <div class="card mb-4 border-primary">
                <div class="card-header fw-semibold text-primary">Shipment</div>
                <div class="card-body text-center">
                    <i class="bx bx-package" style="font-size: 3rem; color: #696cff;"></i>
                    <p class="mt-2">Order is ready to be shipped.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#shipmentModal">
                        <i class="bx bx-package me-1"></i> Create Shipment
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($order->shiprocket_order_id)
    <div class="card mb-4 border-success">
        <div class="card-header fw-semibold text-success d-flex justify-content-between align-items-center">
            <span><i class="bx bx-package me-1"></i> ShipRocket Shipment</span>
            <a href="https://app.shiprocket.in/orders/order-detail/{{ $order->shiprocket_order_id }}" target="_blank" class="btn btn-sm btn-outline-success">
                <i class="bx bx-link-external me-1"></i> View on ShipRocket
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>ShipRocket Order ID:</strong> {{ $order->shiprocket_order_id }}</p>
                    <p><strong>AWB Number:</strong>
                        @if($order->awb_number)
                            <span class="badge bg-success">{{ $order->awb_number }}</span>
                        @else
                            <span class="text-muted">Awaiting courier assignment</span>
                        @endif
                    </p>
                    <p><strong>Courier:</strong> {{ $order->shipment_carrier ?: 'To be assigned' }}</p>
                    <p><strong>Shipped At:</strong> {{ $order->shipped_at ? $order->shipped_at->format('d M Y h:i A') : 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <form action="{{ route('admin.orders.pickup', $order->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <i class="bx bx-calendar me-1"></i> Schedule Pickup
                            </button>
                        </form>
                        <a href="{{ route('admin.orders.label', $order->id) }}" class="btn btn-sm btn-outline-success">
                            <i class="bx bx-file me-1"></i> Generate Label
                        </a>
                        <a href="{{ route('admin.orders.invoice', $order->id) }}" class="btn btn-sm btn-outline-info">
                            <i class="bx bx-receipt me-1"></i> Generate Invoice
                        </a>
                        @if($order->awb_number)
                            <a href="{{ route('admin.orders.track', $order->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-line-chart me-1"></i> Track
                            </a>
                        @endif
                    </div>
                    @if(!$order->awb_number)
                        <div class="alert alert-info p-2 small mb-0 mt-2">
                            <i class="bx bx-info-circle me-1"></i>
                            Order created on ShipRocket. Generate label/invoice after courier is assigned.
                        </div>
                    @endif
                </div>
            </div>
            @if(session('tracking'))
                <hr>
                <div class="mt-2">
                    <h6 class="fw-semibold">Tracking Info</h6>
                    <pre class="small bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;">{{ json_encode(session('tracking'), JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header fw-semibold">Order Items</div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>₹{{ number_format($item->price, 2) }}</td>
                        <td>₹{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <th colspan="4" class="text-end">Grand Total</th>
                        <th>₹{{ number_format($order->total_amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="shipmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.orders.addShipment', $order->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create ShipRocket Shipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Enter package dimensions. The courier will be checked and AWB assigned automatically.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Weight (kg)</label>
                            <input type="number" step="0.1" name="weight" class="form-control" placeholder="e.g. 0.5" min="0.1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Length (cm)</label>
                            <input type="number" step="0.1" name="length" class="form-control" placeholder="e.g. 10" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Breadth (cm)</label>
                            <input type="number" step="0.1" name="breadth" class="form-control" placeholder="e.g. 10" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Height (cm)</label>
                            <input type="number" step="0.1" name="height" class="form-control" placeholder="e.g. 10" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-package me-1"></i> Create via ShipRocket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection