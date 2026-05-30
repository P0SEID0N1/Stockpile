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
        Schema::create('holding_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_job_id')->nullable()->constrained()->nullOnDelete();
            $table->date('snapshot_date');
            $table->decimal('quantity', 20, 6);
            $table->decimal('cost_basis_total', 16, 2);
            $table->decimal('market_value', 16, 2);
            $table->decimal('price_per_unit', 20, 6)->nullable();
            $table->string('source_type')->default('csv');
            $table->string('source_reference')->nullable();
            $table->timestamps();

            $table->unique(['holding_id', 'snapshot_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holding_snapshots');
    }
};
