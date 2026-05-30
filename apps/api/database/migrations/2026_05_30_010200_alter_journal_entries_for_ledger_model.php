<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('asset_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
            $table->string('source_type')->default('manual')->after('amount');
            $table->foreignId('linked_entry_id')->nullable()->after('source_type')->constrained('journal_entries')->nullOnDelete();
            $table->json('metadata')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_id');
            $table->dropConstrainedForeignId('linked_entry_id');
            $table->dropColumn(['source_type', 'metadata']);
        });
    }
};
