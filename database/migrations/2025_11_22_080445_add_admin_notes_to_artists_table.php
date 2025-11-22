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
        Schema::table('artists', function (Blueprint $table) {
            $table->text('notes_admin')->nullable()->after('can_reapply_at')->comment('Admin notes for approval/rejection');
            $table->foreignId('approved_by')->nullable()->after('notes_admin')->constrained('users')->comment('Admin who approved/rejected the artist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['notes_admin', 'approved_by']);
        });
    }
};