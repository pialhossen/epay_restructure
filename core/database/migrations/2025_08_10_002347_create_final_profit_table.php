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
        Schema::create('final_profits', function (Blueprint $table) {
            $table->id();
            $table->string('business_day', 20)->nullable();
            $table->string('currency', 50)->nullable();
            $table->integer('currency_id')->nullable();
            $table->float('cs_sent_avg_rate')->nullable();
            $table->float('currency_reserved')->nullable();
            $table->float('currency_total')->nullable();
            $table->float('all_currency_total')->nullable();
            $table->float('all_active_users_balance')->nullable();
            $table->float('total_profit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('final_profit');
    }
};
