<?php

use App\Http\Controllers\DemoLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', DemoLoginController::class);

// The demo-aware login component must live on its own path: noerd registers an
// ANY `/login` route that redirects to `/noerd/login`, and with cached routes
// (as on the deployed demo) that redirect wins over a same-path override, which
// would drop the prefilled demo credentials.
Route::middleware(['web', 'guest'])->group(function (): void {
    Route::livewire('demo-login', 'auth.login')->name('login');
});
