<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('tenant_apps')->where('name', 'MEDIA')->exists()) {
            DB::table('tenant_apps')->insert([
                'title' => 'Media',
                'name' => 'MEDIA',
                'icon' => 'media::icons.app',
                'route' => 'media.dashboard',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('tenant_apps')->where('name', 'MEDIA')->delete();
    }
};
