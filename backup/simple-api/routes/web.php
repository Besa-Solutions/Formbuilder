<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/setup', function () {
    $credentials = [
        'email'    => 'admin@admin.com',
        'password' => 'password'
    ];

    // Als de gebruiker niet geauthenticeerd kan worden, maak de gebruiker aan.
    if (!Auth::attempt($credentials)) {
        $user = new \App\Models\User();
        $user->name     = 'Admin';
        $user->email    = 'admin@admin.com';
        $user->password = Hash::make($credentials['password']);
        $user->save();
    }

    // Probeer opnieuw in te loggen.
    if (Auth::attempt($credentials)) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
    
        $adminToken  = $user->createToken('admin-token', ['create', 'update', 'delete']);
        $updateToken = $user->createToken('update-token', ['create', 'update']);
        $basicToken  = $user->createToken('basic-token');
    
        return [
            'admin'  => $adminToken->plainTextToken,
            'update' => $updateToken->plainTextToken,
            'basic'  => $basicToken->plainTextToken,
        ];
    }

    return response()->json(['message' => 'Authentication failed'], 401);
});