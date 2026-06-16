<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shiprocket_order_id')->nullable()->after('shipped_at');
            $table->string('awb_number')->nullable()->after('shiprocket_order_id');
            $table->text('shiprocket_response')->nullable()->after('awb_number');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shiprocket_order_id', 'awb_number', 'shiprocket_response']);
        });
    }
};
