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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('artwork_id')->constrained()->onDelete('cascade');
            $table->foreignId('artist_id')->constrained()->onDelete('cascade');
            $table->integer('total_amount');
            $table->integer('commission');
            $table->integer('artist_earnings');
            $table->string('payment_method', 50);
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_id', 255)->nullable();
            $table->enum('shipping_status', ['pending', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->string('tracking_number', 255)->nullable();
            $table->string('buyer_name', 255);
            $table->string('buyer_phone', 20);
            $table->text('shipping_address');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
