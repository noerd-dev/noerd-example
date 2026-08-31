<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noerd\Models\Tenant;

uses(RefreshDatabase::class);

test('the application redirects from root', function () {
    Tenant::forceCreate(['id' => 1, 'name' => 'Default', 'hash' => 'default-hash']);

    $response = $this->get('/');

    $response->assertRedirect();
});
