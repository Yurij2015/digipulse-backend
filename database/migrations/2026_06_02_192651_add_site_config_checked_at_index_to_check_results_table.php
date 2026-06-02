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
        Schema::table('check_results', static function (Blueprint $table) {
            $table->index(
                ['site_id', 'configuration_id', 'checked_at'],
                'check_results_site_config_checked_at_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('check_results', static function (Blueprint $table) {
            $table->dropIndex('check_results_site_config_checked_at_index');
        });
    }
};
