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
        Schema::create('artworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description');
            $table->integer('price'); // سيكون في الـ validation بين 500 و 10000
            $table->string('category', 100);
            $table->string('dimensions', 100)->nullable();
            $table->string('materials', 255)->nullable();
            $table->json('images'); // مصفوفة URLs للصور
            $table->enum('status', ['available', 'pending', 'sold'])->default('available');
            $table->integer('views')->default(0);
            $table->integer('likes')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artworks');
    }
};
