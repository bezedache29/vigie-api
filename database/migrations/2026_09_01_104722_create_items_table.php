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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('title')->nullable();
            $table->string('url')->nullable();
            $table->longText('raw_content')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->enum('status', ['pending', 'summarized', 'ignored', 'error'])->default('pending');
            $table->timestamps();

            $table->unique(['source_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
