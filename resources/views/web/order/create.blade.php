@extends('web.layouts.app')
@section('content')
<section class="py-4 bg-light" style="min-height: 80vh;">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Checkout</h2>
            <p class="text-muted">Fill in the details below to place a new order</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <form action="{{ route('web.orders.store') }}" method="POST" id="checkout-form">
                    @csrf

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}</div>
                    @endif

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom-0 pt-3">
                            <h5 class="fw-semibold mb-0">
                                <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; font-size: 14px;">1</span>
                                Customer Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">First Name</label>
                                    <input type="text" name="first_name" class="form-control form-control-lg @error('first_name') is-invalid @enderror" placeholder="John" value="{{ old('first_name') }}" required>
                                    @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Last Name</label>
                                    <input type="text" name="last_name" class="form-control form-control-lg @error('last_name') is-invalid @enderror" placeholder="Doe" value="{{ old('last_name') }}" required>
                                    @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email</label>
                                    <input type="email" name="email" class="form-control form-control-lg @error('email') is-invalid @enderror" placeholder="john@example.com" value="{{ old('email') }}" required>
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Phone</label>
                                    <input type="text" name="phone" class="form-control form-control-lg @error('phone') is-invalid @enderror" placeholder="+1 234 567 890" value="{{ old('phone') }}">
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom-0 pt-3">
                            <h5 class="fw-semibold mb-0">
                                <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; font-size: 14px;">2</span>
                                Shipping Address
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <textarea name="shipping_address" class="form-control form-control-lg @error('shipping_address') is-invalid @enderror" rows="3" placeholder="Street, city, state, zip code, country..." required>{{ old('shipping_address') }}</textarea>
                                @error('shipping_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-semibold">Order Notes <span class="text-muted">(optional)</span></label>
                                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Any special instructions...">{{ old('notes') }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom-0 pt-3">
                            <h5 class="fw-semibold mb-0">
                                <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; font-size: 14px;">3</span>
                                Order Items
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="items-container">
                                @php $oldItems = old('items', [['product_name' => '', 'quantity' => 1, 'price' => '']]); @endphp
                                @foreach($oldItems as $idx => $item)
                                <div class="item-row border rounded p-3 mb-3 bg-white">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label small fw-semibold">Product</label>
                                            <input type="text" name="items[{{ $idx }}][product_name]" class="form-control @error('items.' . $idx . '.product_name') is-invalid @enderror" placeholder="Product name" value="{{ $item['product_name'] ?? '' }}" required>
                                            @error('items.' . $idx . '.product_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small fw-semibold">Qty</label>
                                            <input type="number" name="items[{{ $idx }}][quantity]" class="form-control item-qty @error('items.' . $idx . '.quantity') is-invalid @enderror" min="1" value="{{ $item['quantity'] ?? 1 }}" required>
                                            @error('items.' . $idx . '.quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">Price (₹)</label>
                                            <input type="number" step="0.01" name="items[{{ $idx }}][price]" class="form-control item-price @error('items.' . $idx . '.price') is-invalid @enderror" placeholder="0.00" min="0" value="{{ $item['price'] ?? '' }}" required>
                                            @error('items.' . $idx . '.price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-danger remove-item w-100" {{ count($oldItems) < 2 ? 'disabled' : '' }}>
                                                <i class="bi bi-trash3"></i> &times;
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" id="add-item" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                                + Add Item
                            </button>
                            @error('items') <br><small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                            <i class="bi bi-check2-circle"></i> Place Order
                        </button>
                        <a href="{{ route('web.orders.index') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header bg-white border-bottom-0 pt-3">
                        <h5 class="fw-semibold mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0" id="summary-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="summary-body">
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">Subtotal</th>
                                        <th class="text-end" id="summary-subtotal">₹0.00</th>
                                    </tr>
                                    <tr>
                                        <th colspan="2" class="text-end">Items</th>
                                        <th class="text-end" id="summary-count">0</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0 text-center py-3">
                        <button type="submit" form="checkout-form" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-lock-fill me-1"></i> Place Order — <span id="summary-total-btn">₹0.00</span>
                        </button>
                        <small class="text-muted d-block mt-2">Secure checkout &middot; Demo order</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script>
    let itemIndex = {{ count(old('items', [['product_name' => '']])) }};

    function updateSummary() {
        const rows = document.querySelectorAll('.item-row');
        const tbody = document.getElementById('summary-body');
        tbody.innerHTML = '';
        let subtotal = 0;

        rows.forEach((row, idx) => {
            const nameInput = row.querySelector('input[name^="items"][name$="[product_name]"]');
            const qtyInput = row.querySelector('.item-qty');
            const priceInput = row.querySelector('.item-price');
            const name = nameInput ? nameInput.value.trim() || 'Item ' + (idx + 1) : 'Item ' + (idx + 1);
            const qty = parseInt(qtyInput ? qtyInput.value : 0) || 0;
            const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
            const total = qty * price;
            subtotal += total;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${name}</td>
                <td class="text-center">${qty}</td>
                <td class="text-end">₹${total.toFixed(2)}</td>
            `;
            tbody.appendChild(tr);
        });

        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-muted text-center">No items added</td></tr>';
        }

        document.getElementById('summary-subtotal').textContent = '₹' + subtotal.toFixed(2);
        document.getElementById('summary-count').textContent = rows.length;
        document.getElementById('summary-total-btn').textContent = '₹' + subtotal.toFixed(2);
    }

    document.addEventListener('input', function(e) {
        if (e.target.closest('.item-row') && (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price') || e.target.name?.includes('product_name'))) {
            updateSummary();
        }
    });

    document.getElementById('add-item').addEventListener('click', function() {
        const container = document.getElementById('items-container');
        const div = document.createElement('div');
        div.className = 'item-row border rounded p-3 mb-3 bg-white';
        div.innerHTML = `
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Product</label>
                    <input type="text" name="items[${itemIndex}][product_name]" class="form-control" placeholder="Product name" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Qty</label>
                    <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-qty" min="1" value="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Price ($)</label>
                    <input type="number" step="0.01" name="items[${itemIndex}][price]" class="form-control item-price" placeholder="0.00" min="0" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger remove-item w-100">&times;</button>
                </div>
            </div>
        `;
        container.appendChild(div);
        itemIndex++;
        updateRemoveButtons();
        updateSummary();
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item') || e.target.closest('.remove-item')) {
            const btn = e.target.classList.contains('remove-item') ? e.target : e.target.closest('.remove-item');
            btn.closest('.item-row').remove();
            updateRemoveButtons();
            updateSummary();
        }
    });

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.item-row');
        rows.forEach((row, idx) => {
            const btn = row.querySelector('.remove-item');
            if (btn) btn.disabled = rows.length === 1;
        });
    }

    updateSummary();
</script>
@endsection
