<?php

use App\Models\Admin;
use App\Models\Exchange;
use App\Models\User;
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
        Schema::create('balance_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->onDelete("CASCADE");
            $table->bigInteger('amount');
            $table->string('via');
            $table->foreignIdFor(Exchange::class)->nullable()->constrained()->onDelete('SET NULL');
            $table->foreignIdFor(Admin::class)->nullable()->constrained()->onDelete('SET NULL');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_statements');
    }
};
