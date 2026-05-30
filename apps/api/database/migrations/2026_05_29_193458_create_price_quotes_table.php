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
        Schema::create('price_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 20, 6);
            $table->string('currency', 3)->default('USD');
            $table->date('price_date');
            $table->timestamp('quoted_at');
            $table->string('source')->default('demo');
            $table->decimal('day_change', 20, 6)->nullable();
            $table->decimal('day_change_percent', 10, 4)->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'quoted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_quotes');
    }
};
