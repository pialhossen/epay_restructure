<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->string('customer_selling_rate')->after('sell_rate');
            $table->string('customer_buying_rate')->after('customer_selling_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropColumn('customer_selling_rate');
            $table->dropColumn('customer_buying_rate');
        });
    }
};
