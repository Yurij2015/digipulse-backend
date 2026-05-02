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
        Schema::table('users', function (Blueprint $table) {
            $table->text('name')->nullable()->change();
            $table->text('email')->nullable()->change();
            $table->text('google_id')->nullable()->change();
            $table->text('telegram_chat_id')->nullable()->change();
            $table->text('first_name')->nullable()->change();
            $table->text('last_name')->nullable()->change();
            $table->text('google_nickname')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->change();
            $table->string('email', 255)->nullable()->change();
            $table->string('google_id', 255)->nullable()->change();
            $table->string('telegram_chat_id', 255)->nullable()->change();
            $table->string('first_name', 255)->nullable()->change();
            $table->string('last_name', 255)->nullable()->change();
            $table->string('google_nickname', 255)->nullable()->change();
        });
    }
};
