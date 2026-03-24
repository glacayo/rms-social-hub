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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->jsonb('media_paths')->nullable();       // array of local file paths
            $table->enum('media_type', ['image', 'video', 'none'])->default('none');
            $table->enum('post_type', ['post', 'reel', 'story'])->default('post');
            $table->enum('status', [
                'draft',
                'scheduled',
                'sending',
                'published',
                'failed',
                'cancelled',
            ])->default('draft');
            $table->timestamp('scheduled_at')->nullable();  // UTC
            $table->timestamp('published_at')->nullable();  // UTC, set on success
            $table->text('failed_reason')->nullable();
            $table->smallInteger('retry_count')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index(['status', 'scheduled_at']); // for scheduler query
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
