<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'touch.token'])->get('/user', function (Request $request) {
    $user = $request->user();

    return [
        'id' => $user->uuid,
        'name' => $user->name,
        'email' => $user->email,
    ];
});
