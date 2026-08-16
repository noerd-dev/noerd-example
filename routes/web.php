<?php

use App\Http\Controllers\DemoLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', DemoLoginController::class);

// Override noerd's login route with the app's demo-aware login component.
Route::middleware(['web', 'guest'])->group(function (): void {
    Route::livewire('login', 'auth.login')->name('login');
});
