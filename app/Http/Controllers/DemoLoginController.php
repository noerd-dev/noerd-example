<?php

namespace App\Http\Controllers;

use Database\Seeders\MediaTestDataSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Noerd\Database\Seeders\SetupLanguageSeeder;
use Noerd\Enums\Profile;
use Noerd\Media\Models\Media;
use Noerd\Models\NoerdUser as User;
use Noerd\Models\Tenant;
use Noerd\Models\TenantApp;
use Noerd\Models\UserSetting;
use Nywerk\Study\Database\Seeders\StudyTestDataSeeder;
use Nywerk\Study\Models\StudyMaterial;

class DemoLoginController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('noerd.apps');
        }

        $tenant = Tenant::query()->first();

        if (! $tenant) {
            $tenant = new Tenant;
            $tenant->name = 'Default';
            $tenant->uuid = Str::uuid()->toString();
            $tenant->save();
        }

        $this->ensureStudyAppInstalled($tenant);
        $this->ensureMediaAppInstalled($tenant);
        $this->ensureSeedersHaveRun();

        $password = 'demo';

        $user = User::forceCreate([
            'name' => 'Demo User',
            'email' => 'demo-'.Str::uuid().'@demo.test',
            'password' => $password,
            'email_verified_at' => now(),
            'is_demo' => true,
            'super_admin' => false,
        ]);

        $user->tenants()->attach($tenant->id, ['profile_key' => Profile::User->value]);

        UserSetting::create([
            'user_id' => $user->id,
            'selected_tenant_id' => $tenant->id,
            'locale' => 'en',
        ]);

        session()->flash('demo_email', $user->email);
        session()->flash('demo_password', $password);

        return redirect()->route('login');
    }

    private function ensureSeedersHaveRun(): void
    {
        (new SetupLanguageSeeder)->run();

        if (StudyMaterial::withoutGlobalScopes()->count() === 0) {
            (new StudyTestDataSeeder)->run();
        }

        if (Media::withoutGlobalScopes()->count() === 0) {
            app(MediaTestDataSeeder::class)->run();

            // Seeded media is served through the public/storage symlink, which
            // does not exist on a freshly deployed environment.
            Artisan::call('storage:link');
        }
    }

    private function ensureStudyAppInstalled(Tenant $tenant): void
    {
        $studyApp = TenantApp::query()->firstOrCreate(
            ['name' => 'STUDY'],
            [
                'title' => 'Study',
                'icon' => 'study::icons.app',
                'route' => 'study.dashboard',
                'is_active' => true,
            ],
        );

        if (! $tenant->tenantApps()->where('tenant_app_id', $studyApp->id)->exists()) {
            $tenant->tenantApps()->attach($studyApp->id);
        }
    }

    private function ensureMediaAppInstalled(Tenant $tenant): void
    {
        $mediaApp = TenantApp::query()->firstOrCreate(
            ['name' => 'MEDIA'],
            [
                'title' => 'Media',
                'icon' => 'media::icons.app',
                'route' => 'media.dashboard',
                'is_active' => true,
            ],
        );

        if (! $tenant->tenantApps()->where('tenant_app_id', $mediaApp->id)->exists()) {
            $tenant->tenantApps()->attach($mediaApp->id);
        }
    }
}
