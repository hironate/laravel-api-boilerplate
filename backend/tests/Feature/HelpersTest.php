<?php

use App\Models\User;


// Helper functions


function registerUser($name = 'John Doe', $email = 'john@example.com', $password = 'password')
{

    test()->seed();

    return test()->post('/api/auth/register', [
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ]);
}

function loginUser($email = 'john@example.com', $password = 'password')
{
    return test()->post('/api/auth/login', [
        'email' => $email,
        'password' => $password,
    ]);
}

function verifyUser($email = 'john@example.com')
{
    $user = User::where('email', $email)->first();
    $user->email_verified_at = now();
    $user->save();
}

function getAuthToken()
{
    registerUser();
    verifyUser();
    $response = loginUser();
    return $response->json()["data"]["token"];
}
