<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Noerd\Customer\Models\Customer;
use Noerd\Models\Tenant;
use Noerd\Models\TenantApp;
use Nywerk\Study\Models\StudyMaterial;

uses(RefreshDatabase::class);

// `demo:reset` runs every two hours through `migrate:fresh --seed`, so the
// seeder alone has to bring both apps and their demo data back.
it('installs the study and customer apps with their demo data', function (): void {
    $this->seed(DatabaseSeeder::class);

    $tenant = Tenant::query()->firstOrFail();

    foreach (['STUDY', 'CUSTOMER'] as $appName) {
        $app = TenantApp::query()->where('name', $appName)->first();

        expect($app)->not->toBeNull()
            ->and($tenant->tenantApps()->where('tenant_app_id', $app->id)->exists())->toBeTrue();
    }

    expect(StudyMaterial::withoutGlobalScopes()->count())->toBeGreaterThan(0)
        ->and(Customer::withoutGlobalScopes()->count())->toBeGreaterThan(0);
});

it('leaves no media app behind', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(TenantApp::query()->where('name', 'MEDIA')->exists())->toBeFalse();
});
