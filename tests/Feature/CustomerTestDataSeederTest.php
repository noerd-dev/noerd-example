<?php

use Database\Seeders\CustomerTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Models\CustomerAddress;
use Noerd\Models\Tenant;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Tenant::forceCreate(['id' => 1, 'name' => 'Default', 'uuid' => Str::uuid()->toString()]);
});

it('seeds customers with their default addresses', function (): void {
    $this->seed(CustomerTestDataSeeder::class);

    expect(Customer::count())->toBe(6)
        ->and(CustomerAddress::count())->toBe(6);

    Customer::all()->each(function (Customer $customer): void {
        expect($customer->tenant_id)->toBe(1)
            ->and($customer->company_name)->not->toBeNull()
            ->and($customer->email)->not->toBeNull()
            ->and($customer->default_invoice_address_id)->not->toBeNull()
            ->and($customer->default_delivery_address_id)->toBe($customer->default_invoice_address_id)
            ->and($customer->defaultInvoiceAddress->tenant_id)->toBe(1)
            ->and($customer->defaultInvoiceAddress->fingerprint)->not->toBeNull();
    });
});

// The customer model is auditable — without the published audits table the
// detail screen's activity log dies with "no such table: audits".
it('records and reads the audit trail of a customer', function (): void {
    // Seeding runs in the console, which the auditing package skips by default.
    config(['audit.console' => true]);

    $this->seed(CustomerTestDataSeeder::class);

    $customer = Customer::query()->firstOrFail();
    $customer->update(['internal_comment' => 'Updated by the test.']);

    expect($customer->audits()->count())->toBeGreaterThan(0);
});
