<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs databases that were created before the noerd system table migrations
 * were consolidated. Those create-migrations bail out early via `Schema::hasTable()`,
 * so columns added during the consolidation are missing on existing installations
 * (e.g. `setup_languages.tenant_id`).
 *
 * Every step is idempotent, so this migration is safe on fresh databases too.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->repairSetupLanguages();

        $this->addColumnIfMissing('tenants', 'logo', function (Blueprint $table): void {
            $table->string('logo')->nullable()->after('name');
        });

        $this->addColumnIfMissing('tenant_app', 'sort_order', function (Blueprint $table): void {
            $table->integer('sort_order')->default(0)->after('is_hidden');
        });

        $this->addColumnIfMissing('noerd_settings', 'detail_theme', function (Blueprint $table): void {
            $table->string('detail_theme')->nullable()->after('currency');
        });

        $this->addColumnIfMissing('noerd_settings', 'detail_theme_enforced', function (Blueprint $table): void {
            $table->boolean('detail_theme_enforced')->default(false)->after('detail_theme');
        });
    }

    public function down(): void
    {
        // Repair only, nothing to roll back.
    }

    /**
     * Brings `setup_languages` up to the consolidated schema: tenant scoping column,
     * backfilled values, index, unique constraint and foreign key.
     */
    private function repairSetupLanguages(): void
    {
        if (! Schema::hasTable('setup_languages') || Schema::hasColumn('setup_languages', 'tenant_id')) {
            return;
        }

        Schema::table('setup_languages', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        $defaultTenantId = DB::table('tenants')->orderBy('id')->value('id');

        if ($defaultTenantId === null) {
            // No tenants yet: orphaned rows can never be resolved, and the seeder
            // recreates the defaults per tenant.
            DB::table('setup_languages')->delete();
        } else {
            DB::table('setup_languages')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }

        Schema::table('setup_languages', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });

        $this->dropUniqueOnCodeOnly();

        Schema::table('setup_languages', function (Blueprint $table): void {
            $table->index('tenant_id');
            $table->unique(['tenant_id', 'code']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    /**
     * Removes the legacy `unique(code)` constraint that the tenant-scoped
     * `unique(tenant_id, code)` replaces.
     */
    private function dropUniqueOnCodeOnly(): void
    {
        foreach (Schema::getIndexes('setup_languages') as $index) {
            if ($index['unique'] === true && $index['columns'] === ['code']) {
                Schema::table('setup_languages', function (Blueprint $table) use ($index): void {
                    $table->dropIndex($index['name']);
                });
            }
        }
    }

    private function addColumnIfMissing(string $table, string $column, Closure $definition): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, $definition);
    }
};
