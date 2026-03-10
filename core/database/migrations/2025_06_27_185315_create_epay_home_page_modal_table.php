<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('epay_home_page_modal', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('button_name')->nullable();
            $table->string('button_link')->nullable();
            $table->text('image_link')->nullable();
            $table->integer('status')->default(1);
            $table->text('remarks')->nullable();
            $table->integer('cb')->nullable();
            $table->timestamp('cd')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->integer('ub')->nullable();
            $table->timestamp('ud')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epay_home_page_modal');
    }
};
