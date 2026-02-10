<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Noerd\Database\Seeders\SetupLanguageSeeder;
use Noerd\Models\Profile;
use Noerd\Models\Tenant;
use Noerd\Models\TenantApp;
use Noerd\Models\User;
use Noerd\Models\UserSetting;
use Nywerk\Study\Database\Seeders\StudyTestDataSeeder;
use Nywerk\Study\Models\StudyMaterial;

class DemoLoginController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/noerd-home');
        }

        $tenant = Tenant::query()->first();

        if (! $tenant) {
            $tenant = new Tenant;
            $tenant->name = 'Default';
            $tenant->uuid = Str::uuid()->toString();
            $tenant->save();

            Profile::create(['tenant_id' => $tenant->id, 'key' => 'USER', 'name' => 'User']);
            Profile::create(['tenant_id' => $tenant->id, 'key' => 'ADMIN', 'name' => 'Admin']);
        }

        $this->ensureStudyAppInstalled($tenant);
        $this->ensureSeedersHaveRun();

        $profile = Profile::query()
            ->where('tenant_id', $tenant->id)
            ->where('key', 'USER')
            ->firstOrFail();

        $password = 'demo';

        $user = User::forceCreate([
            'name' => 'Demo User',
            'email' => 'demo-'.Str::uuid().'@demo.test',
            'password' => $password,
            'email_verified_at' => now(),
            'is_demo' => true,
            'super_admin' => false,
        ]);

        $user->tenants()->attach($tenant->id, ['profile_id' => $profile->id]);

        UserSetting::create([
            'user_id' => $user->id,
            'selected_tenant_id' => $tenant->id,
            'locale' => 'en',
        ]);

        session()->flash('demo_email', $user->email);
        session()->flash('demo_password', $password);

        return redirect('/login');
    }

    private function ensureSeedersHaveRun(): void
    {
        (new SetupLanguageSeeder)->run();

        if (StudyMaterial::withoutGlobalScopes()->count() === 0) {
            (new StudyTestDataSeeder)->run();
        }
    }

    private function ensureStudyAppInstalled(Tenant $tenant): void
    {
        $studyApp = TenantApp::query()->firstOrCreate(
            ['name' => 'STUDY'],
            [
                'title' => 'Study',
                'icon' => 'study::icons.app',
                'route' => 'study.study-dashboard',
                'is_active' => true,
            ],
        );

        if (! $tenant->tenantApps()->where('tenant_app_id', $studyApp->id)->exists()) {
            $tenant->tenantApps()->attach($studyApp->id);
        }
    }
}
