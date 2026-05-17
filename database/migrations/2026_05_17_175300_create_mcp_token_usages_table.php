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
        Schema::create('mcp_token_usages', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('token_id');
            $table->string('endpoint', 64);
            $table->date('date');
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['token_id', 'endpoint', 'date']);
            $table->index('user_id');
            $table->index('token_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('token_id')->references('id')->on('personal_access_tokens')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcp_token_usages');
    }
};
