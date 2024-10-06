<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

uses(RefreshDatabase::class);


it('should get the authenticated user', function () {
    Event::fake()->except([]);
    $token = getAuthToken();

    $response = test()->get('/api/user/me', headers: ['Authorization' => "Bearer $token"]);
    $response->assertStatus(200);
});
