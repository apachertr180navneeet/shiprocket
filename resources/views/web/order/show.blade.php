@extends('web.layouts.app')
@section('content')
<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Order #{{ $order->order_number }}</h2>
                    <div>
                        @if($order->status !== 'cancelled' && $order->status !== 'completed')
                        <form action="{{ route('web.orders.destroy', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">Cancel Order</button>
                        </form>
                        @endif
                        <a href="{{ route('web.orders.edit', $order->id) }}" class="btn btn-warning">Edit</a>
                        <a href="{{ route('web.orders.index') }}" class="btn btn-secondary">Back</a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
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
                                <p><strong>Total:</strong> ₹{{ number_format($order->total_amount, 2) }}</p>
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
                    </div>
                </div>

                @if($order->awb_number)
                <div class="card shadow-sm mb-3 border-success">
                    <div class="card-header fw-semibold text-success d-flex justify-content-between align-items-center">
                        <span>Shipment Tracking</span>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="loadTracking()">
                            <i class="bx bx-refresh me-1"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body" id="trackingBody">
                        <div class="text-center py-3">
                            <p><strong>AWB:</strong> <span class="badge bg-success fs-6">{{ $order->awb_number }}</span></p>
                            <p><strong>Courier:</strong> {{ $order->shipment_carrier ?: 'N/A' }}</p>
                            <button type="button" class="btn btn-primary" onclick="loadTracking()">
                                <i class="bx bx-line-chart me-1"></i> Track Now
                            </button>
                        </div>
                        <div id="trackingTimeline" style="display:none;"></div>
                    </div>
                </div>
                @endif

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
        </div>
    </div>
</section>

@if($order->awb_number)
<script>
async function loadTracking() {
    const body = document.getElementById('trackingBody');
    const timeline = document.getElementById('trackingTimeline');

    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-success" role="status"></div><p class="mt-2">Fetching tracking data...</p></div>';

    try {
        const res = await fetch('{{ route("web.orders.track", $order->id) }}');
        const data = await res.json();

        if (data.error) {
            body.innerHTML = `<div class="alert alert-danger mb-0">${data.error}</div>`;
            return;
        }

        const td = data.tracking_data || data;
        const scans = td.scans || [];
        const statusTitle = td.shipment_status_title || td.current_status || 'In Transit';
        const delivered = td.delivered_date || td.delivered_on || null;
        const pickupDate = td.pickup_scheduled_date || null;

        let scansHtml = '';
        if (scans.length) {
            scans.reverse().forEach((s, i) => {
                const isLast = i === scans.length - 1;
                const isActive = isLast || i === 0;
                scansHtml += `
                    <div class="d-flex mb-3">
                        <div class="d-flex flex-column align-items-center me-3" style="width:30px;">
                            <div class="rounded-circle border border-2 ${isLast ? 'bg-success border-success' : 'border-secondary'}" style="width:14px;height:14px;"></div>
                            ${!isLast ? '<div class="flex-grow-1" style="width:2px;background:#ddd;min-height:30px;"></div>' : ''}
                        </div>
                        <div class="${isLast ? '' : 'pb-2'}">
                            <strong>${s.status || s.activity || 'Scan'}</strong>
                            <div class="text-muted small">${s.location || ''}</div>
                            <div class="text-muted small">${s.date || ''}</div>
                        </div>
                    </div>
                `;
            });
        } else {
            scansHtml = `<p class="text-muted">No scan details available yet.</p>`;
        }

        let statusBadge = 'info';
        if (delivered) statusBadge = 'success';
        else if (statusTitle.toLowerCase().includes('cancelled')) statusBadge = 'danger';
        else if (statusTitle.toLowerCase().includes('pickup')) statusBadge = 'warning';

        timeline.style.display = 'block';
        timeline.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded">
                <div>
                    <span class="badge bg-${statusBadge} fs-6">${statusTitle}</span>
                    ${delivered ? `<span class="ms-2 text-muted small">Delivered: ${delivered}</span>` : ''}
                    ${pickupDate ? `<span class="ms-2 text-muted small">Pickup: ${pickupDate}</span>` : ''}
                </div>
                <a href="https://shiprocket.co/tracking/{{ $order->awb_number }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-link-external me-1"></i>Open
                </a>
            </div>
            <div class="px-2">
                <h6 class="fw-semibold mb-3">Tracking Timeline</h6>
                ${scansHtml}
            </div>
        `;

        body.innerHTML = '';
        body.appendChild(timeline);

    } catch (err) {
        body.innerHTML = `<div class="alert alert-danger mb-0">Failed to load tracking: ${err.message}</div>`;
    }
}

document.addEventListener('DOMContentLoaded', loadTracking);
</script>
@endif
@endsection