<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benchmark_price_histories', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 32);
            $table->string('provider_symbol', 32);
            $table->string('label');
            $table->date('price_date');
            $table->decimal('close_price', 20, 6);
            $table->string('source')->default('tiingo');
            $table->timestamps();

            $table->unique(['symbol', 'price_date']);
            $table->index(['provider_symbol', 'price_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benchmark_price_histories');
    }
};
