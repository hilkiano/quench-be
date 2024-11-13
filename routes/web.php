<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::get('/auth/{provider}', "authRedirect");
    Route::get('/auth/{provider}/callback', "authCallback");
});
