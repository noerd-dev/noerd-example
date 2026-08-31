<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Noerd\Database\Seeders\SetupLanguageSeeder;
use Noerd\Enums\Profile;
use Noerd\Models\NoerdUser as User;
use Noerd\Models\Tenant;
use Noerd\Models\TenantApp;
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

        $mediaApp = TenantApp::query()->firstOrCreate(
            ['name' => 'MEDIA'],
            [
                'title' => 'Media',
                'icon' => 'media::icons.app',
                'route' => 'media.dashboard',
                'is_active' => true,
            ],
        );

        $tenant->tenantApps()->attach($mediaApp->id);

        $this->call(SetupLanguageSeeder::class);
        $this->call(StudyTestDataSeeder::class);
        $this->call(MediaTestDataSeeder::class);

        $user = User::forceCreate([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_demo' => false,
            'super_admin' => false,
        ]);

        $user->tenants()->attach($tenant->id, ['profile_key' => Profile::User->value]);

        UserSetting::create([
            'user_id' => $user->id,
            'selected_tenant_id' => $tenant->id,
            'locale' => 'en',
        ]);
    }
}
