<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        if (! DB::table('tenant_apps')->where('name', 'STUDY')->exists()) {
            DB::table('tenant_apps')->insert([
                'title' => 'Study',
                'name' => 'STUDY',
                'icon' => 'study::icons.app',
                'route' => 'study.study-materials',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('tenant_apps')->where('name', 'STUDY')->delete();
    }
};
