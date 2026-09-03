<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Noerd\Models\NoerdUser as User;
use Noerd\Models\Tenant;

uses(RefreshDatabase::class);

it('shows the seeded customers in the customer list', function (): void {
    $this->get('/');

    $user = User::query()->where('is_demo', true)->firstOrFail();
    $this->withSession(['noerd.selected_tenant_id' => Tenant::query()->value('id')]);

    Livewire::actingAs($user)
        ->test('customer::customers-list')
        ->assertSuccessful()
        ->assertSee('Nordwind Logistics')
        ->assertSee('Helios Energie GmbH')
        ->assertSee('anna.schneider@nordwind-logistics.test');
});
