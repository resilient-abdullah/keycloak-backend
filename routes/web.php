<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KeycloakAuthController;

Route::get('/', function () {
    return 'Laravel + Keycloak is running';
});

Route::get('/login', [KeycloakAuthController::class, 'redirectToKeycloak']);
Route::get('/callback', [KeycloakAuthController::class, 'handleCallback']);
Route::get('/logout', [KeycloakAuthController::class, 'logout']);