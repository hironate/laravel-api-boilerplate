<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

uses(RefreshDatabase::class);

it('should register a new user successfully', function () {
    test()->seed();
    Event::fake([Registered::class]);

    $response = $this->postJson('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'email', 'email_verified_at', 'google_id', 'updated_at', 'created_at'],
            'statusCode'
        ]);

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    Event::assertDispatched(Registered::class);
});

it('should not register a user with an existing email', function () {
    test()->seed();
    User::factory()->create(['email' => 'existing@example.com']);
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Validation failed',
            'data' => [
                'email' => ['The email has already been taken.']
            ],
            'statusCode' => 422
        ]);
});

it('should not register a user with invalid data', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertStatus(422)    
        ->assertJson([
            'message' => 'Validation failed',
            'data' => [
                'name' => [
                    'The name field must be a string.',
                    'The name field is required.'
                ],
                'email' => [
                    'The email field must be a valid email address.'
                ],
                'password' => [
                    'The password field must be at least 8 characters.'
                ]
            ],
            'statusCode' => 422
        ]);
});

it('should not register a user with missing required fields', function () {
    $response = $this->postJson('/api/auth/register', []);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Validation failed',
            'data' => [
                'name' => ['The name field is required.'],
                'email' => ['The email field is required.'],
                'password' => ['The password field is required.']
            ],
            'statusCode' => 422
        ]);
});

it('should trim whitespace from name and email', function () {
    test()->seed();
    $response = $this->postJson('/api/auth/register', [
        'name' => '  John Doe  ',
        'email' => '  john@example.com  ',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);


    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

it('should enforce minimum password length', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Validation failed',
            'data' => [
                'password' => ['The password field must be at least 8 characters.']
            ],
            'statusCode' => 422
        ]);
});
