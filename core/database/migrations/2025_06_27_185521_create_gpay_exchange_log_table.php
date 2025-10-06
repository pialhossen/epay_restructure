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
        Schema::create('gpay_exchange_log', function (Blueprint $table) {
            $table->id();
            $table->integer('exchange_id')->nullable();
            $table->string('exchange_status')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->text('remarks')->nullable();
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
        Schema::dropIfExists('gpay_exchange_log');
    }
};
