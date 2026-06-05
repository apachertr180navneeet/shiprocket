<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ url('/') }}">
            {{ config('app.name') }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('web.orders.*') ? 'active' : '' }}" href="{{ route('web.orders.index') }}">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('web.orders.create') ? 'active' : '' }}" href="{{ route('web.orders.create') }}">Create Order</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
