<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Noerd\Models\NoerdUser as User;
use Noerd\Models\Tenant;

uses(RefreshDatabase::class);

it('shows the seeded media in the media library', function (): void {
    Storage::fake(config('media.disk'));

    $this->get('/');

    $user = User::query()->where('is_demo', true)->firstOrFail();
    $this->withSession(['noerd.selected_tenant_id' => Tenant::query()->value('id')]);

    Livewire::actingAs($user)
        ->test('media::media-list')
        ->assertSuccessful()
        ->assertSee('Brand Assets')
        ->assertSee('Product Photos')
        ->assertSee('hero-banner.png');
});
