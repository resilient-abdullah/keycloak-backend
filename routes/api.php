<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware('keycloak.auth')->get('/user', function (Request $request) {
    return response()->json($request->get('keycloak_user'));
});
