<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Noerd\Models\Tenant;

uses(RefreshDatabase::class);

test('the application redirects from root', function () {
    Tenant::factory()->create(['id' => 1, 'name' => 'Default']);

    $response = $this->get('/');

    $response->assertRedirect();
});
