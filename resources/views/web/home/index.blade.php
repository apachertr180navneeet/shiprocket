@extends('web.layouts.app')
@section('content')
<section class="py-5 text-center bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold text-primary">Welcome to {{ config('app.name') }}</h1>
                <p class="lead text-muted mb-4">Manage your orders easily with our simple order processing system.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('web.orders.create') }}" class="btn btn-primary btn-lg px-4">Create New Order</a>
                    <a href="{{ route('web.orders.index') }}" class="btn btn-outline-primary btn-lg px-4">View All Orders</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-primary h-100 text-center p-4">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Create Orders</h5>
                        <p class="card-text text-muted">Add new orders with multiple items and shipping details.</p>
                        <a href="{{ route('web.orders.create') }}" class="btn btn-sm btn-primary">Create</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success h-100 text-center p-4">
                    <div class="card-body">
                        <h5 class="card-title text-success">Track Orders</h5>
                        <p class="card-text text-muted">View and manage order status from pending to completed.</p>
                        <a href="{{ route('web.orders.index') }}" class="btn btn-sm btn-success">View Orders</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning h-100 text-center p-4">
                    <div class="card-body">
                        <h5 class="card-title text-warning">API Access</h5>
                        <p class="card-text text-muted">Integrate with our REST API for programmatic access.</p>
                        <a href="#" class="btn btn-sm btn-warning">API Docs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
