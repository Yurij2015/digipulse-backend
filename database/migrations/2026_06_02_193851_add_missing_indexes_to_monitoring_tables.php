<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_check_configurations', static function (Blueprint $table) {
            $table->index(['site_id', 'is_active'], 'scc_site_id_is_active_index');
            $table->index(['is_active', 'last_checked_at'], 'scc_is_active_last_checked_at_index');
            $table->index('check_type_id', 'scc_check_type_id_index');
        });

        Schema::table('sites', static function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'sites_user_id_created_at_index');
            $table->index(['user_id', 'project_id'], 'sites_user_id_project_id_index');
            $table->index('is_active', 'sites_is_active_index');
        });

        Schema::table('check_results', static function (Blueprint $table) {
            $table->index(
                ['site_id', 'status', 'checked_at'],
                'check_results_site_id_status_checked_at_index',
            );
        });

        Schema::table('check_result_archives', static function (Blueprint $table) {
            $table->index(['site_id', 'created_at'], 'cra_site_id_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('site_check_configurations', static function (Blueprint $table) {
            $table->dropIndex('scc_site_id_is_active_index');
            $table->dropIndex('scc_is_active_last_checked_at_index');
            $table->dropIndex('scc_check_type_id_index');
        });

        Schema::table('sites', static function (Blueprint $table) {
            $table->dropIndex('sites_user_id_created_at_index');
            $table->dropIndex('sites_user_id_project_id_index');
            $table->dropIndex('sites_is_active_index');
        });

        Schema::table('check_results', static function (Blueprint $table) {
            $table->dropIndex('check_results_site_id_status_checked_at_index');
        });

        Schema::table('check_result_archives', static function (Blueprint $table) {
            $table->dropIndex('cra_site_id_created_at_index');
        });
    }
};
