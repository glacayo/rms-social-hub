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
        Schema::create('post_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('page_id')->references('id')->on('facebook_pages');
            $table->enum('status', ['pending', 'published', 'failed'])->default('pending');
            $table->string('facebook_post_id', 100)->nullable(); // returned by Meta on success
            $table->timestamp('published_at')->nullable();
            $table->text('failed_reason')->nullable();

            $table->unique(['post_id', 'page_id']);
            $table->index('post_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_pages');
    }
};
