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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('page_id')->nullable()->references('id')->on('facebook_pages')->nullOnDelete();
            $table->foreignId('post_id')->nullable()->references('id')->on('posts')->nullOnDelete();
            $table->string('action', 100);
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // NO updated_at — this table is immutable

            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
