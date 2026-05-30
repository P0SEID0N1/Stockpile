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
        Schema::create('benchmark_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->nullable()->constrained()->nullOnDelete();
            $table->string('symbol');
            $table->string('label');
            $table->date('series_date');
            $table->decimal('close_price', 20, 6);
            $table->string('source')->default('demo');
            $table->timestamps();

            $table->unique(['symbol', 'series_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benchmark_series');
    }
};
