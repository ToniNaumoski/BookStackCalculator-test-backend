<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('grid_size');
            $table->json('grid_data'); // Store the N×N grid
            $table->integer('visible_stacks');
            $table->json('visibility_details')->nullable(); // Store detailed visibility info
            $table->timestamps();

            // Add index for sorting by creation time
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_calculations');
    }
};
