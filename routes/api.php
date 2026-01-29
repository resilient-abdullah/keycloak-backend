<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware('keycloak.auth')->get('/user', function (Request $request) {
    return response()->json($request->get('keycloak_user'));
});

Route::middleware(['keycloak.auth', 'keycloak.role:user'])->get('/user-area', function () {
    return response()->json(['message' => 'User access OK']);
});

Route::middleware(['keycloak.auth', 'keycloak.role:admin'])->get('/admin-area', function () {
    return response()->json(['message' => 'Admin access OK']);
});