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
        Schema::create('holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 20, 6)->default(0);
            $table->decimal('cost_basis_total', 16, 2)->default(0);
            $table->decimal('market_value', 16, 2)->nullable();
            $table->timestamp('price_as_of')->nullable();
            $table->date('last_snapshot_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'asset_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holdings');
    }
};
