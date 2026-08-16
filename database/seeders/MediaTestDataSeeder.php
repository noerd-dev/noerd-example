<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Noerd\Media\Models\Media;
use Noerd\Media\Models\MediaFolder;
use Noerd\Media\Models\MediaTag;
use Noerd\Media\Services\ImagePreviewService;

class MediaTestDataSeeder extends Seeder
{
    private const TENANT_ID = 1;

    /**
     * Demo files per folder. The folder key `null` places the files in the media library root.
     *
     * @var array<string, array<int, array{name: string, color: string}>>
     */
    private const FILES = [
        'Logos' => [
            ['name' => 'logo-primary.png', 'color' => '#1d4ed8'],
            ['name' => 'logo-inverted.png', 'color' => '#0f172a'],
            ['name' => 'logo-icon.png', 'color' => '#2563eb'],
        ],
        'Product Photos' => [
            ['name' => 'headphones-front.png', 'color' => '#0f766e'],
            ['name' => 'headphones-side.png', 'color' => '#14b8a6'],
            ['name' => 'keyboard-top.png', 'color' => '#7c3aed'],
            ['name' => 'desk-setup.png', 'color' => '#be123c'],
        ],
        'Team' => [
            ['name' => 'portrait-anna-smith.png', 'color' => '#d97706'],
            ['name' => 'portrait-james-miller.png', 'color' => '#ca8a04'],
        ],
        'root' => [
            ['name' => 'hero-banner.png', 'color' => '#0369a1'],
            ['name' => 'newsletter-header.png', 'color' => '#4d7c0f'],
            ['name' => 'social-preview.png', 'color' => '#9333ea'],
        ],
    ];

    public function __construct(private readonly ImagePreviewService $imagePreviewService) {}

    public function run(): void
    {
        $this->clearDemoFiles();

        $brandAssets = MediaFolder::factory()->create([
            'tenant_id' => self::TENANT_ID,
            'name' => 'Brand Assets',
        ]);

        $folders = [
            'Logos' => MediaFolder::factory()->create([
                'tenant_id' => self::TENANT_ID,
                'parent_id' => $brandAssets->id,
                'name' => 'Logos',
            ]),
            'Product Photos' => MediaFolder::factory()->create([
                'tenant_id' => self::TENANT_ID,
                'name' => 'Product Photos',
            ]),
            'Team' => MediaFolder::factory()->create([
                'tenant_id' => self::TENANT_ID,
                'name' => 'Team',
            ]),
        ];

        $tags = collect(['Website', 'Print', 'Social Media'])
            ->mapWithKeys(fn (string $name): array => [
                $name => MediaTag::firstOrCreate([
                    'tenant_id' => self::TENANT_ID,
                    'name' => $name,
                ]),
            ]);

        foreach (self::FILES as $folderName => $files) {
            foreach ($files as $file) {
                $media = $this->createMedia(
                    $file['name'],
                    $file['color'],
                    $folders[$folderName]->id ?? null,
                );

                $media->tags()->attach(
                    $tags->random(rand(1, 2))->pluck('id')->all(),
                );
            }
        }
    }

    /**
     * Write a placeholder image to the media disk and create the matching
     * media record — including a real thumbnail, so the library renders
     * previews just like uploaded files.
     */
    private function createMedia(string $name, string $color, ?int $folderId): Media
    {
        $disk = Storage::disk(config('media.disk'));
        $path = self::TENANT_ID.'/'.Str::random().'_'.$name;

        $image = (new ImageManager(new Driver))
            ->create(1200, 800)
            ->fill($color)
            ->toPng();

        $disk->put($path, (string) $image);

        $media = Media::factory()->create([
            'tenant_id' => self::TENANT_ID,
            'folder_id' => $folderId,
            'type' => 'image',
            'name' => $name,
            'extension' => 'png',
            'path' => $path,
            'disk' => config('media.disk'),
            'size' => $disk->size($path),
        ]);

        $media->update(['thumbnail' => $this->imagePreviewService->regenerateThumbnail($media)]);

        return $media;
    }

    /**
     * Remove previously seeded files so repeated `demo:reset` runs do not leave
     * orphaned files behind on the media disk.
     */
    private function clearDemoFiles(): void
    {
        Storage::disk(config('media.disk'))->deleteDirectory((string) self::TENANT_ID);
    }
}
