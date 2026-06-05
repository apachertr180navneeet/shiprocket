@extends('web.layouts.app')
@section('content')
<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Create New Order</h2>
                </div>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ route('web.orders.store') }}" method="POST">
                    @csrf
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">User</label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">Select User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->full_name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                                @error('user_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Shipping Address</label>
                                <textarea name="shipping_address" class="form-control" rows="2" required></textarea>
                                @error('shipping_address') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>

                            <hr>
                            <h5 class="fw-semibold">Order Items</h5>
                            <div id="items-container">
                                <div class="row g-2 item-row mb-2">
                                    <div class="col-md-5">
                                        <input type="text" name="items[0][product_name]" class="form-control" placeholder="Product Name" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="items[0][quantity]" class="form-control" placeholder="Qty" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" step="0.01" name="items[0][price]" class="form-control" placeholder="Price" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger remove-item w-100" disabled>-</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="add-item" class="btn btn-sm btn-outline-primary mt-2">+ Add Item</button>
                            @error('items') <br><small class="text-danger">{{ $message }}</small> @enderror

                            <hr>
                            <button type="submit" class="btn btn-primary">Create Order</button>
                            <a href="{{ route('web.orders.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
@section('script')
<script>
    let itemIndex = 1;
    document.getElementById('add-item').addEventListener('click', function() {
        const container = document.getElementById('items-container');
        const row = document.createElement('div');
        row.className = 'row g-2 item-row mb-2';
        row.innerHTML = `
            <div class="col-md-5">
                <input type="text" name="items[${itemIndex}][product_name]" class="form-control" placeholder="Product Name" required>
            </div>
            <div class="col-md-2">
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control" placeholder="Qty" min="1" value="1" required>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" name="items[${itemIndex}][price]" class="form-control" placeholder="Price" min="0" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger remove-item w-100">-</button>
            </div>
        `;
        container.appendChild(row);
        itemIndex++;
        updateRemoveButtons();
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            e.target.closest('.item-row').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.item-row');
        rows.forEach((row, index) => {
            const btn = row.querySelector('.remove-item');
            btn.disabled = rows.length === 1;
        });
    }
</script>
@endsection
