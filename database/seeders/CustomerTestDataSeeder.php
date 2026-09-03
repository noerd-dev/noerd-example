<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Noerd\Customer\Models\Customer;
use Noerd\Customer\Services\CustomerAddressService;
use Noerd\Helpers\TenantHelper;
use Noerd\Models\Tenant;

class CustomerTestDataSeeder extends Seeder
{
    /**
     * Demo customers with the address that becomes their invoice and delivery default.
     *
     * @var array<int, array{company_name: string, name: string, email: string, phone: string, internal_comment: string, address: array{address_line_1: string, postal_code: string, locality: string, country_code: string}}>
     */
    private const CUSTOMERS = [
        [
            'company_name' => 'Nordwind Logistics',
            'name' => 'Anna Schneider',
            'email' => 'anna.schneider@nordwind-logistics.test',
            'phone' => '+49 40 1234560',
            'internal_comment' => 'Framework agreement runs until the end of the year.',
            'address' => [
                'address_line_1' => 'Hafenstraße 12',
                'postal_code' => '20359',
                'locality' => 'Hamburg',
                'country_code' => 'DE',
            ],
        ],
        [
            'company_name' => 'Helios Energie GmbH',
            'name' => 'Thomas Brandt',
            'email' => 'thomas.brandt@helios-energie.test',
            'phone' => '+49 89 9876540',
            'internal_comment' => 'Invoices are only accepted as PDF.',
            'address' => [
                'address_line_1' => 'Sonnenallee 4',
                'postal_code' => '80331',
                'locality' => 'München',
                'country_code' => 'DE',
            ],
        ],
        [
            'company_name' => 'Blumen Meyer',
            'name' => 'Katrin Meyer',
            'email' => 'katrin.meyer@blumen-meyer.test',
            'phone' => '+49 221 4455660',
            'internal_comment' => 'Deliveries in the morning only.',
            'address' => [
                'address_line_1' => 'Marktplatz 7',
                'postal_code' => '50667',
                'locality' => 'Köln',
                'country_code' => 'DE',
            ],
        ],
        [
            'company_name' => 'Alpin Sport AG',
            'name' => 'Marc Bühler',
            'email' => 'marc.buehler@alpin-sport.test',
            'phone' => '+41 31 5566770',
            'internal_comment' => 'Seasonal business, main order in September.',
            'address' => [
                'address_line_1' => 'Bahnhofstrasse 22',
                'postal_code' => '3011',
                'locality' => 'Bern',
                'country_code' => 'CH',
            ],
        ],
        [
            'company_name' => 'Donau Handel KG',
            'name' => 'Sophie Gruber',
            'email' => 'sophie.gruber@donau-handel.test',
            'phone' => '+43 1 2233440',
            'internal_comment' => 'Contact by phone preferred.',
            'address' => [
                'address_line_1' => 'Praterstraße 45',
                'postal_code' => '1020',
                'locality' => 'Wien',
                'country_code' => 'AT',
            ],
        ],
        [
            'company_name' => 'Werkstatt Nord',
            'name' => 'Jens Petersen',
            'email' => 'jens.petersen@werkstatt-nord.test',
            'phone' => '+49 431 7788990',
            'internal_comment' => 'Pays by direct debit.',
            'address' => [
                'address_line_1' => 'Kieler Weg 3',
                'postal_code' => '24103',
                'locality' => 'Kiel',
                'country_code' => 'DE',
            ],
        ],
    ];

    public function __construct(private readonly CustomerAddressService $customerAddressService) {}

    public function run(): void
    {
        $tenantId = TenantHelper::getSelectedTenantId() ?? Tenant::query()->value('id');

        foreach (self::CUSTOMERS as $customerData) {
            $customer = Customer::withoutGlobalScopes()->create([
                'tenant_id' => $tenantId,
                'company_name' => $customerData['company_name'],
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'internal_comment' => $customerData['internal_comment'],
            ]);

            // Through the service, so the seeded address carries the same
            // fingerprint and default assignment the detail form writes.
            $this->customerAddressService->upsertFor(
                $customer,
                $customerData['address'],
                asInvoiceDefault: true,
                asDeliveryDefault: true,
            );
        }
    }
}
