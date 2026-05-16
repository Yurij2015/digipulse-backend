<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions before insertion
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Safely create agency role using firstOrCreate to avoid duplication
        Role::firstOrCreate(['name' => 'agency']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Remove the agency role
        Role::where('name', 'agency')->delete();
    }
};
