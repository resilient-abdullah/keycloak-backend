<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostUpdateRequestController;

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

Route::middleware('keycloak.auth')->group(function () {

    // Anyone authenticated (user, moderator, editor, admin)
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{post}', [PostController::class, 'show']);

    // Admin / Editor
    Route::post('/posts', [PostController::class, 'store']);

    // Admin ONLY (direct update)
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);

    // Moderator workflow
    Route::post('/posts/{post}/update-request', [PostUpdateRequestController::class, 'store']);

    // Review workflow (Editor/Admin)
    Route::get('/update-requests', [PostUpdateRequestController::class, 'index']);
    Route::post('/update-requests/{id}/approve', [PostUpdateRequestController::class, 'approve']);
    Route::post('/update-requests/{id}/reject', [PostUpdateRequestController::class, 'reject']);
});
