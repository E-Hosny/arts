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
        Schema::table('users', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['artist', 'buyer', 'admin'])->default('buyer')->after('password');
            }
            
            if (!Schema::hasColumn('users', 'email_verified')) {
                $table->boolean('email_verified')->default(false)->after('role');
            }
            
            if (!Schema::hasColumn('users', 'avatar_url')) {
                $table->text('avatar_url')->nullable()->after('email_verified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'avatar_url')) {
                $table->dropColumn('avatar_url');
            }
            
            if (Schema::hasColumn('users', 'email_verified')) {
                $table->dropColumn('email_verified');
            }
            
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};