<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

it('resets the demo database successfully', function () {
    $kernel = Mockery::mock(app(Kernel::class))->makePartial();

    $kernel->shouldReceive('call')
        ->with('migrate:fresh', ['--seed' => true, '--force' => true])
        ->once();

    $kernel->shouldReceive('call')
        ->with('storage:link')
        ->once();

    Artisan::swap($kernel);

    $this->artisan('demo:reset')
        ->expectsOutputToContain('Resetting demo database...')
        ->expectsOutputToContain('Demo database has been reset successfully.')
        ->assertSuccessful();
});
