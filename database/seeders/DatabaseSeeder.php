<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Noerd\Database\Seeders\SetupLanguageSeeder;
use Noerd\Models\Profile;
use Noerd\Models\Tenant;
use Noerd\Models\TenantApp;
use Noerd\Models\User;
use Noerd\Models\UserSetting;
use Nywerk\Study\Database\Seeders\StudyTestDataSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = new Tenant;
        $tenant->name = 'Default';
        $tenant->uuid = Str::uuid()->toString();
        $tenant->save();

        Profile::create(['tenant_id' => $tenant->id, 'key' => 'USER', 'name' => 'User']);
        Profile::create(['tenant_id' => $tenant->id, 'key' => 'ADMIN', 'name' => 'Admin']);

        $studyApp = TenantApp::query()->firstOrCreate(
            ['name' => 'STUDY'],
            [
                'title' => 'Study',
                'icon' => 'study::icons.app',
                'route' => 'study.study-materials',
                'is_active' => true,
            ],
        );

        $tenant->tenantApps()->attach($studyApp->id);

        $this->call(SetupLanguageSeeder::class);
        $this->call(StudyTestDataSeeder::class);

        $profile = Profile::query()
            ->where('tenant_id', $tenant->id)
            ->where('key', 'USER')
            ->firstOrFail();

        $user = User::forceCreate([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_demo' => false,
            'super_admin' => false,
        ]);

        $user->tenants()->attach($tenant->id, ['profile_id' => $profile->id]);

        UserSetting::create([
            'user_id' => $user->id,
            'selected_tenant_id' => $tenant->id,
            'locale' => 'en',
        ]);
    }
}
