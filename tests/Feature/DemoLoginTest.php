<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Noerd\Customer\Models\Customer;
use Noerd\Enums\Profile;
use Noerd\Helpers\NoerdAuth;
use Noerd\Models\NoerdUser as User;
use Noerd\Models\SetupLanguage;
use Noerd\Models\Tenant;
use Noerd\Models\TenantApp;
use Noerd\Models\UserSetting;
use Nywerk\Study\Models\StudyMaterial;

uses(RefreshDatabase::class);

it('creates a demo user and redirects to login with the credentials in the session', function () {
    $tenant = Tenant::factory()->create(['name' => 'Default']);

    $response = $this->get('/');

    $response->assertRedirect('/demo-login');
    $this->assertGuest();

    $user = User::query()->where('is_demo', true)->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Demo User')
        ->and($user->email)->toStartWith('demo-')
        ->and($user->email)->toEndWith('@demo.test')
        ->and((bool) $user->is_demo)->toBeTrue()
        ->and($user->super_admin)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull();

    expect($user->tenants)->toHaveCount(1);
    expect($user->tenants->first()->id)->toBe($tenant->id);
    expect($user->tenants->first()->pivot->profile_key)->toBe(Profile::User->value);

    $setting = UserSetting::query()->where('user_id', $user->id)->first();
    expect($setting)->not->toBeNull()
        ->and($setting->selected_tenant_id)->toBe($tenant->id)
        ->and($setting->locale)->toBe('en');

    $response->assertSessionHas('demo_email', $user->email);
    $response->assertSessionHas('demo_password', 'demo');
});

it('creates a tenant when none exists', function () {
    expect(Tenant::query()->count())->toBe(0);

    $response = $this->get('/');

    $response->assertRedirect('/demo-login');

    $tenant = Tenant::query()->first();
    expect($tenant)->not->toBeNull()
        ->and($tenant->name)->toBe('Default');

    $studyApp = TenantApp::query()->where('name', 'STUDY')->first();
    expect($studyApp)->not->toBeNull();
    expect($studyApp->route)->toBe('study.dashboard');
    expect($tenant->tenantApps()->where('tenant_app_id', $studyApp->id)->exists())->toBeTrue();

    expect(SetupLanguage::query()->count())->toBeGreaterThan(0);
    expect(StudyMaterial::withoutGlobalScopes()->count())->toBeGreaterThan(0);

    $user = User::query()->where('is_demo', true)->first();
    expect($user)->not->toBeNull();
    expect($user->tenants)->toHaveCount(1);
    expect($user->tenants->first()->id)->toBe($tenant->id);
    expect($user->tenants->first()->pivot->profile_key)->toBe(Profile::User->value);

    $setting = UserSetting::query()->where('user_id', $user->id)->first();
    expect($setting)->not->toBeNull()
        ->and($setting->selected_tenant_id)->toBe($tenant->id);
});

it('assigns the customer app and seeds customer data for a new tenant', function () {
    $this->get('/')->assertRedirect('/demo-login');

    $tenant = Tenant::query()->firstOrFail();
    $customerApp = TenantApp::query()->where('name', 'CUSTOMER')->first();

    expect($customerApp)->not->toBeNull()
        ->and($customerApp->route)->toBe('customers')
        ->and($tenant->tenantApps()->where('tenant_app_id', $customerApp->id)->exists())->toBeTrue()
        ->and(Customer::withoutGlobalScopes()->count())->toBeGreaterThan(0);
});

it('does not duplicate seeded customers on repeated visits', function () {
    $this->get('/');
    $customerCount = Customer::withoutGlobalScopes()->count();

    $this->get('/');

    expect(Customer::withoutGlobalScopes()->count())->toBe($customerCount);
});

it('assigns study app to existing tenant', function () {
    $tenant = Tenant::factory()->create(['name' => 'Default']);

    $studyApp = TenantApp::query()->where('name', 'STUDY')->first();
    expect($tenant->tenantApps()->where('tenant_app_id', $studyApp->id)->exists())->toBeFalse();

    $this->get('/');

    expect($tenant->tenantApps()->where('tenant_app_id', $studyApp->id)->exists())->toBeTrue();
});

it('runs seeders when data is missing', function () {
    $tenant = Tenant::factory()->create(['name' => 'Default']);

    // Since noerd 0.9, Tenant::created seeds the default languages (en, de).
    expect(SetupLanguage::query()->count())->toBe(2);
    expect(StudyMaterial::withoutGlobalScopes()->count())->toBe(0);

    $this->get('/');

    expect(SetupLanguage::query()->count())->toBeGreaterThan(0);
    expect(StudyMaterial::withoutGlobalScopes()->count())->toBeGreaterThan(0);
});

it('does not duplicate seeded data on repeated visits', function () {
    $tenant = Tenant::factory()->create(['name' => 'Default']);

    $this->get('/');

    $languageCount = SetupLanguage::query()->count();
    $materialCount = StudyMaterial::withoutGlobalScopes()->count();

    $this->get('/');

    expect(SetupLanguage::query()->count())->toBe($languageCount);
    expect(StudyMaterial::withoutGlobalScopes()->count())->toBe($materialCount);
});

it('does not duplicate study app when already assigned', function () {
    $tenant = Tenant::factory()->create(['name' => 'Default']);

    $studyApp = TenantApp::query()->where('name', 'STUDY')->firstOrFail();
    $tenant->tenantApps()->attach($studyApp->id);

    $this->get('/');

    expect(TenantApp::query()->where('name', 'STUDY')->count())->toBe(1);
    expect($tenant->tenantApps()->where('tenant_app_id', $studyApp->id)->count())->toBe(1);
});

it('renders the demo-aware login component on the login route', function () {
    $tenant = Tenant::factory()->create(['name' => 'Default']);

    $this->get('/')->assertRedirect('/demo-login');

    $this->get('/demo-login')
        ->assertSuccessful()
        ->assertSeeLivewire('auth.login')
        ->assertSee('@demo.test');
});

// noerd registers an ANY `/login` route that redirects to `/noerd/login`. With
// cached routes that redirect wins over a same-path override, so the demo login
// must keep its own path or the prefilled credentials are lost.
it('does not serve the demo login from the path noerd redirects away', function () {
    expect(route('login', absolute: false))->toBe('/demo-login');
});

it('sends guests from the noerd login screen to the demo login', function () {
    Tenant::factory()->create(['name' => 'Default']);

    $this->get('/noerd/login')->assertRedirect('/');

    $this->followingRedirects()
        ->get('/noerd/login')
        ->assertSuccessful()
        ->assertSeeLivewire('auth.login')
        ->assertSee('@demo.test');
});

it('sends guests from the /login path to the demo login', function () {
    Tenant::factory()->create(['name' => 'Default']);

    $this->followingRedirects()
        ->get('/login')
        ->assertSuccessful()
        ->assertSeeLivewire('auth.login')
        ->assertSee('@demo.test');
});

it('prefills the credentials when the login screen is opened directly', function () {
    Tenant::factory()->create(['name' => 'Default']);

    $this->get('/demo-login')->assertRedirect('/');

    $this->followingRedirects()
        ->get('/demo-login')
        ->assertSuccessful()
        ->assertSee('@demo.test');
});

it('keeps the credentials prefilled on a refresh', function () {
    Tenant::factory()->create(['name' => 'Default']);

    $this->get('/');

    // Flashed credentials would survive the first render only.
    $this->get('/demo-login')->assertSuccessful()->assertSee('@demo.test');
    $this->get('/demo-login')->assertSuccessful()->assertSee('@demo.test');
});

it('reuses the demo user of the running session', function () {
    Tenant::factory()->create(['name' => 'Default']);

    $this->get('/');
    $this->get('/');
    $this->get('/demo-login');

    expect(User::query()->where('is_demo', true)->count())->toBe(1);
});

it('provisions a new demo user when the one in the session is gone', function () {
    Tenant::factory()->create(['name' => 'Default']);

    $this->withSession([
        'demo_email' => 'deleted-demo@demo.test',
        'demo_password' => 'demo',
    ]);

    $this->followingRedirects()
        ->get('/demo-login')
        ->assertSuccessful()
        ->assertDontSee('deleted-demo@demo.test')
        ->assertSee('@demo.test');

    expect(User::query()->where('is_demo', true)->count())->toBe(1);
});

it('logs in on the guard noerd protects its routes with', function () {
    $user = User::forceCreate([
        'name' => 'Demo User',
        'email' => 'demo-guard@demo.test',
        'password' => 'demo',
        'email_verified_at' => now(),
        'is_demo' => true,
        'super_admin' => false,
    ]);

    Livewire::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'demo')
        ->call('login');

    // Authenticating on the default guard instead leaves noerd seeing a guest,
    // so the redirect to the apps bounces back to noerd's own login screen.
    expect(Auth::guard(config('noerd.auth.guard'))->check())->toBeTrue()
        ->and(NoerdAuth::user()?->id)->toBe($user->id);
});

it('sends a logged-in visitor from the login screens to the apps', function () {
    $user = User::forceCreate([
        'name' => 'Demo User',
        'email' => 'demo-loggedin@demo.test',
        'password' => 'demo',
        'email_verified_at' => now(),
        'is_demo' => true,
        'super_admin' => false,
    ]);

    Auth::guard(config('noerd.auth.guard'))->login($user);

    $this->get('/demo-login')->assertRedirect(route('noerd.apps'));
    $this->get('/noerd/login')->assertRedirect(route('noerd.apps'));
});

it('drops a session that is only authenticated on the default guard', function () {
    Tenant::factory()->create(['name' => 'Default']);

    $stale = User::forceCreate([
        'name' => 'Stale User',
        'email' => 'stale@demo.test',
        'password' => 'password',
        'email_verified_at' => now(),
        'is_demo' => false,
        'super_admin' => false,
    ]);

    // What a demo visitor is left with when the app logs in on a guard noerd
    // does not read: noerd sees a guest, Laravel's `guest` middleware does not.
    Auth::guard('web')->login($stale);

    $this->followingRedirects()
        ->get('/demo-login')
        ->assertSuccessful()
        ->assertSee('@demo.test');

    expect(Auth::guard('web')->check())->toBeFalse();
});

it('can log in with demo credentials', function () {
    $user = User::forceCreate([
        'name' => 'Demo User',
        'email' => 'demo-test@demo.test',
        'password' => 'demo',
        'email_verified_at' => now(),
        'is_demo' => true,
        'super_admin' => false,
    ]);

    Livewire::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'demo')
        ->call('login')
        ->assertRedirect(route('noerd.apps', absolute: false));

    $this->assertAuthenticatedAs($user);
});

it('shows the auth background panel in demo mode', function () {
    $this->withSession([
        'demo_email' => 'demo-test@demo.test',
        'demo_password' => 'demo',
    ]);

    Livewire::test('auth.login')
        ->assertSet('isDemo', true)
        ->assertDontSee('invisible');
});

it('redirects without creating a new user when already logged in', function () {
    $user = User::forceCreate([
        'name' => 'Existing User',
        'email' => 'existing@example.com',
        'password' => 'password',
        'email_verified_at' => now(),
        'is_demo' => false,
        'super_admin' => false,
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('noerd.apps'));

    expect(User::query()->where('is_demo', true)->count())->toBe(0);
});

it('cleans up old demo users', function () {
    $oldDemo = User::forceCreate([
        'name' => 'Old Demo',
        'email' => 'old-demo@demo.test',
        'password' => 'password',
        'email_verified_at' => now(),
        'is_demo' => true,
        'super_admin' => false,
        'created_at' => now()->subHours(25),
        'updated_at' => now()->subHours(25),
    ]);

    $newDemo = User::forceCreate([
        'name' => 'New Demo',
        'email' => 'new-demo@demo.test',
        'password' => 'password',
        'email_verified_at' => now(),
        'is_demo' => true,
        'super_admin' => false,
    ]);

    $regularUser = User::forceCreate([
        'name' => 'Regular User',
        'email' => 'regular@example.com',
        'password' => 'password',
        'email_verified_at' => now(),
        'is_demo' => false,
        'super_admin' => false,
    ]);

    $this->artisan('demo:cleanup', ['--hours' => 24])
        ->expectsOutputToContain('Deleted 1 demo user(s)')
        ->assertSuccessful();

    expect(User::query()->find($oldDemo->id))->toBeNull();
    expect(User::query()->find($newDemo->id))->not->toBeNull();
    expect(User::query()->find($regularUser->id))->not->toBeNull();
});
