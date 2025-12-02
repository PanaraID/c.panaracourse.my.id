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
        Schema::dropIfExists('frontend_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('frontend_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_id')->unique()->index();
            $table->string('session_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->enum('level', ['debug', 'info', 'warn', 'error'])->index();
            $table->string('message');
            $table->json('data')->nullable();
            $table->json('context');
            $table->text('stack_trace')->nullable();
            $table->string('url');
            $table->string('user_agent');
            $table->string('ip_address')->nullable();
            $table->string('type')->nullable()->index(); // js_error, ajax_request, user_action, etc.
            $table->timestamp('log_timestamp'); // Frontend timestamp
            $table->timestamps();

            // Indexes for performance
            $table->index(['level', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['session_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('log_timestamp');
            
            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
