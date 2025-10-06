<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gpay_currency_discount_charge', function (Blueprint $table) {
            $table->id();
            $table->integer('currency_id')->nullable();
            $table->string('rules_for')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->double('charge_percent')->nullable();
            $table->double('charge_fixed')->nullable();
            $table->double('from')->nullable();
            $table->double('to')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->integer('created_by')->nullable();
            $table->timestamp('created_date')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('updated_by')->nullable();
            $table->timestamp('updated_date')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gpay_currency_discount_charge');
    }
};
