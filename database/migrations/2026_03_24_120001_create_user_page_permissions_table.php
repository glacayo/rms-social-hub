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
        Schema::create('user_page_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('page_id')->references('id')->on('facebook_pages')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'page_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_page_permissions');
    }
};
