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

        // Every login URL of the demo routes guests through here, so the demo
        // user of the running session is reused instead of piling up a new one
        // per visit.
        if (! self::sessionHasDemoUser()) {
            $this->createDemoUser($tenant);
        }

        return redirect()->route('login');
    }

    /**
     * Whether the session holds the credentials of a demo user that still exists.
     * The credentials outlive the user whenever `demo:cleanup` or `demo:reset`
     * wipes the demo data while the session is still alive.
     */
    public static function sessionHasDemoUser(): bool
    {
        $email = session('demo_email');

        return is_string($email)
            && $email !== ''
            && User::query()->where('email', $email)->where('is_demo', true)->exists();
    }

    private function createDemoUser(Tenant $tenant): void
    {
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

        // Kept in the session, not flashed: the login screen must stay prefilled
        // on a refresh and when it is opened directly.
        session()->put('demo_email', $user->email);
        session()->put('demo_password', $password);
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
