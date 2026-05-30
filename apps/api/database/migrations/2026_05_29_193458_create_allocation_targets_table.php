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
        Schema::create('allocation_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_type')->nullable();
            $table->string('label')->nullable();
            $table->decimal('target_percentage', 8, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocation_targets');
    }
};
