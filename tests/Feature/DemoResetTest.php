<?php

use Illuminate\Support\Facades\Artisan;

it('resets the demo database successfully', function () {
    Artisan::swap(
        Mockery::mock(app(Illuminate\Contracts\Console\Kernel::class))
            ->makePartial()
            ->shouldReceive('call')
            ->with('migrate:fresh', ['--seed' => true, '--force' => true])
            ->once()
            ->getMock()
    );

    $this->artisan('demo:reset')
        ->expectsOutputToContain('Resetting demo database...')
        ->expectsOutputToContain('Demo database has been reset successfully.')
        ->assertSuccessful();
});
