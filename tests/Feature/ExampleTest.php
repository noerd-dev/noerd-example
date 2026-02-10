<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noerd\Models\Tenant;

uses(RefreshDatabase::class);

test('the application redirects from root', function () {
    $tenant = Tenant::forceCreate(['id' => 1, 'name' => 'Default', 'hash' => 'default-hash']);
    $tenant->profiles()->create(['id' => 1, 'key' => 'USER', 'name' => 'User']);

    $response = $this->get('/');

    $response->assertRedirect();
});
