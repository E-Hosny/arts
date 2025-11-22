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
        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('bio')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->enum('subscription_tier', ['basic', 'professional', 'elite'])->default('basic');
            $table->integer('commission_rate')->default(25);
            $table->integer('total_sales')->default(0);
            $table->boolean('verified')->default(false);
            $table->boolean('featured')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('can_reapply_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
