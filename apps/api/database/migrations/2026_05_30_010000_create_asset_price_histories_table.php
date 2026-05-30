<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('price_date');
            $table->decimal('open_price', 20, 6)->nullable();
            $table->decimal('high_price', 20, 6)->nullable();
            $table->decimal('low_price', 20, 6)->nullable();
            $table->decimal('close_price', 20, 6);
            $table->decimal('adj_close_price', 20, 6)->nullable();
            $table->decimal('dividend_cash', 20, 6)->nullable();
            $table->decimal('split_factor', 20, 6)->nullable();
            $table->string('source')->default('tiingo');
            $table->timestamps();

            $table->unique(['asset_id', 'price_date']);
            $table->index(['asset_id', 'price_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_price_histories');
    }
};
