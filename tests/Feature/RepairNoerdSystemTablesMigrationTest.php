<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Noerd\Models\Tenant;

uses(RefreshDatabase::class);

/**
 * Rebuilds `setup_languages` in its pre-consolidation shape: no `tenant_id`,
 * a global unique index on `code`.
 */
function createLegacySetupLanguagesTable(): void
{
    Schema::dropIfExists('setup_languages');

    Schema::create('setup_languages', function (Blueprint $table): void {
        $table->id();
        $table->string('code', 5)->unique();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->boolean('is_default')->default(false);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
}

function runRepairMigration(): void
{
    $migration = require database_path('migrations/2026_08_16_082856_repair_noerd_system_tables.php');

    $migration->up();
}

it('adds the missing tenant_id column to a legacy setup_languages table', function (): void {
    $tenant = Tenant::forceCreate(['name' => 'Default', 'hash' => 'default-hash']);

    createLegacySetupLanguagesTable();

    DB::table('setup_languages')->insert([
        ['code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'sort_order' => 0],
        ['code' => 'de', 'name' => 'Deutsch', 'is_active' => true, 'is_default' => false, 'sort_order' => 1],
    ]);

    runRepairMigration();

    expect(Schema::hasColumn('setup_languages', 'tenant_id'))->toBeTrue();
    expect(DB::table('setup_languages')->where('tenant_id', $tenant->id)->count())->toBe(2);
});

it('replaces the legacy unique index on code with a tenant scoped one', function (): void {
    Tenant::forceCreate(['name' => 'Default', 'hash' => 'default-hash']);

    createLegacySetupLanguagesTable();

    runRepairMigration();

    $uniqueIndexes = collect(Schema::getIndexes('setup_languages'))
        ->where('unique', true)
        ->pluck('columns')
        ->map(fn (array $columns): array => array_values($columns))
        ->all();

    expect($uniqueIndexes)->toContain(['tenant_id', 'code']);
    expect($uniqueIndexes)->not->toContain(['code']);
});

it('removes unassignable language rows when no tenant exists', function (): void {
    createLegacySetupLanguagesTable();

    DB::table('setup_languages')->insert([
        ['code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true, 'sort_order' => 0],
    ]);

    runRepairMigration();

    expect(DB::table('setup_languages')->count())->toBe(0);
});

it('leaves an already consolidated schema untouched when run again', function (): void {
    Tenant::forceCreate(['name' => 'Default', 'hash' => 'default-hash']);

    createLegacySetupLanguagesTable();
    runRepairMigration();

    $indexesAfterRepair = Schema::getIndexes('setup_languages');

    runRepairMigration();

    expect(Schema::getIndexes('setup_languages'))->toEqual($indexesAfterRepair);
    expect(Schema::hasColumn('tenants', 'logo'))->toBeTrue();
    expect(Schema::hasColumn('tenant_app', 'sort_order'))->toBeTrue();
    expect(Schema::hasColumn('noerd_settings', 'detail_theme'))->toBeTrue();
    expect(Schema::hasColumn('noerd_settings', 'detail_theme_enforced'))->toBeTrue();
});
