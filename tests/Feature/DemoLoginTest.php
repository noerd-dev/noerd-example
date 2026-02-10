<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Noerd\Models\Profile;
use Noerd\Models\SetupLanguage;
use Noerd\Models\Tenant;
use Noerd\Models\TenantApp;
use Noerd\Models\User;
use Noerd\Models\UserSetting;
use Nywerk\Study\Models\StudyMaterial;

uses(RefreshDatabase::class);

it('creates a demo user and redirects to login with flash data', function () {
    $tenant = Tenant::forceCreate([
        'name' => 'Default',
        'hash' => 'default-hash',
    ]);

    $profile = Profile::create(['key' => 'USER', 'name' => 'User', 'tenant_id' => $tenant->id]);

    $response = $this->get('/');

    $response->assertRedirect('/login');
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

    $response->assertRedirect('/login');

    $tenant = Tenant::query()->first();
    expect($tenant)->not->toBeNull()
        ->and($tenant->name)->toBe('Default');

    $profiles = Profile::query()->where('tenant_id', $tenant->id)->pluck('key')->toArray();
    expect($profiles)->toContain('USER')
        ->and($profiles)->toContain('ADMIN');

    $studyApp = TenantApp::query()->where('name', 'STUDY')->first();
    expect($studyApp)->not->toBeNull();
    expect($tenant->tenantApps()->where('tenant_app_id', $studyApp->id)->exists())->toBeTrue();

    expect(SetupLanguage::query()->count())->toBeGreaterThan(0);
    expect(StudyMaterial::withoutGlobalScopes()->count())->toBeGreaterThan(0);

    $user = User::query()->where('is_demo', true)->first();
    expect($user)->not->toBeNull();
    expect($user->tenants)->toHaveCount(1);
    expect($user->tenants->first()->id)->toBe($tenant->id);

    $setting = UserSetting::query()->where('user_id', $user->id)->first();
    expect($setting)->not->toBeNull()
        ->and($setting->selected_tenant_id)->toBe($tenant->id);
});

it('assigns study app to existing tenant', function () {
    $tenant = Tenant::forceCreate([
        'name' => 'Default',
        'hash' => 'default-hash',
    ]);

    Profile::create(['key' => 'USER', 'name' => 'User', 'tenant_id' => $tenant->id]);

    $studyApp = TenantApp::query()->where('name', 'STUDY')->first();
    expect($tenant->tenantApps()->where('tenant_app_id', $studyApp->id)->exists())->toBeFalse();

    $this->get('/');

    expect($tenant->tenantApps()->where('tenant_app_id', $studyApp->id)->exists())->toBeTrue();
});

it('runs seeders when data is missing', function () {
    $tenant = Tenant::forceCreate([
        'name' => 'Default',
        'hash' => 'default-hash',
    ]);

    Profile::create(['key' => 'USER', 'name' => 'User', 'tenant_id' => $tenant->id]);

    expect(SetupLanguage::query()->count())->toBe(0);
    expect(StudyMaterial::withoutGlobalScopes()->count())->toBe(0);

    $this->get('/');

    expect(SetupLanguage::query()->count())->toBeGreaterThan(0);
    expect(StudyMaterial::withoutGlobalScopes()->count())->toBeGreaterThan(0);
});

it('does not duplicate seeded data on repeated visits', function () {
    $tenant = Tenant::forceCreate([
        'name' => 'Default',
        'hash' => 'default-hash',
    ]);

    Profile::create(['key' => 'USER', 'name' => 'User', 'tenant_id' => $tenant->id]);

    $this->get('/');

    $languageCount = SetupLanguage::query()->count();
    $materialCount = StudyMaterial::withoutGlobalScopes()->count();

    $this->get('/');

    expect(SetupLanguage::query()->count())->toBe($languageCount);
    expect(StudyMaterial::withoutGlobalScopes()->count())->toBe($materialCount);
});

it('does not duplicate study app when already assigned', function () {
    $tenant = Tenant::forceCreate([
        'name' => 'Default',
        'hash' => 'default-hash',
    ]);

    Profile::create(['key' => 'USER', 'name' => 'User', 'tenant_id' => $tenant->id]);

    $studyApp = TenantApp::query()->where('name', 'STUDY')->firstOrFail();
    $tenant->tenantApps()->attach($studyApp->id);

    $this->get('/');

    expect(TenantApp::query()->where('name', 'STUDY')->count())->toBe(1);
    expect($tenant->tenantApps()->where('tenant_app_id', $studyApp->id)->count())->toBe(1);
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
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
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

    $response->assertRedirect('/noerd-home');

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
