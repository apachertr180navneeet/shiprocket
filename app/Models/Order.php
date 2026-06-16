<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'order_number', 'total_amount', 'status',
        'shipping_address', 'payment_status', 'notes',
        'shipment_tracking', 'shipment_carrier', 'shipped_at',
        'shiprocket_order_id', 'shiprocket_shipment_id', 'awb_number', 'shiprocket_response',
    ];

    protected $casts = [
        'shiprocket_response' => 'array',
        'shipped_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
