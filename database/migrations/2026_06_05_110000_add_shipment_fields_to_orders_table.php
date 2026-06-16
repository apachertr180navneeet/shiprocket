<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipment_tracking')->nullable()->after('notes');
            $table->string('shipment_carrier')->nullable()->after('shipment_tracking');
            $table->timestamp('shipped_at')->nullable()->after('shipment_carrier');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipment_tracking', 'shipment_carrier', 'shipped_at']);
        });
    }
};
