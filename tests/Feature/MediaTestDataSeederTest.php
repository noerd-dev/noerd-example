<?php

use Database\Seeders\MediaTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Noerd\Media\Models\Media;
use Noerd\Media\Models\MediaFolder;
use Noerd\Models\Tenant;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake(config('media.disk'));

    Tenant::forceCreate(['id' => 1, 'name' => 'Default', 'uuid' => Str::uuid()->toString()]);
});

it('seeds media files with folders and tags', function (): void {
    $this->seed(MediaTestDataSeeder::class);

    expect(Media::count())->toBe(12)
        ->and(MediaFolder::count())->toBe(4)
        ->and(MediaFolder::where('name', 'Logos')->first()->parent->name)->toBe('Brand Assets')
        ->and(Media::whereNull('folder_id')->count())->toBe(3);

    Media::all()->each(function (Media $media): void {
        expect($media->tenant_id)->toBe(1)
            ->and($media->tags)->not->toBeEmpty()
            ->and($media->size)->toBeGreaterThan(0);
    });
});

it('stores a real file and thumbnail on the media disk for every seeded media', function (): void {
    $this->seed(MediaTestDataSeeder::class);

    $disk = Storage::disk(config('media.disk'));

    Media::all()->each(function (Media $media) use ($disk): void {
        expect($media->thumbnail)->not->toBeNull()
            ->and($disk->exists($media->path))->toBeTrue()
            ->and($disk->exists($media->thumbnail))->toBeTrue();
    });
});

it('leaves no orphaned files behind when seeded twice', function (): void {
    $this->seed(MediaTestDataSeeder::class);
    $this->seed(MediaTestDataSeeder::class);

    $files = Storage::disk(config('media.disk'))->allFiles();

    // Only the files of the latest run remain: one original plus one thumbnail each.
    expect($files)->toHaveCount(24);
});
