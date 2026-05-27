<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_check_configurations', static function (Blueprint $table) {
            $table->unsignedSmallInteger('consecutive_failures')->default(0)->after('last_checked_at');
            $table->timestamp('confirmed_down_at')->nullable()->after('consecutive_failures');
        });
    }

    public function down(): void
    {
        Schema::table('site_check_configurations', static function (Blueprint $table) {
            $table->dropColumn(['consecutive_failures', 'confirmed_down_at']);
        });
    }
};
