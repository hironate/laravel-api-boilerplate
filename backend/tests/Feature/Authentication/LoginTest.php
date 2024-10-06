<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\QueuedResetPasswordNotification;


uses(RefreshDatabase::class);



// Login Tests

it('should not login a user with unverified email', function () {
    Event::fake()->except([]);
    registerUser();
    $response = loginUser();
    $response->assertStatus(403);

    // Assert the response content
    $response->assertJson([
        'message' => 'Email not verified. Please verify your email before logging in.',
        'data' => null,
        'statusCode' => 403
    ]);
});

it('should successfully login a user with correct credentials', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password', // assuming this is the default password in your factory
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'data' => ['token', 'user'],
            'statusCode'
        ]);
});

it('should not login a user with incorrect password', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials',
            'data' => null,
            'statusCode' => 401
        ]);
});

it('should not login a non-existent user', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials',
            'data' => null,
            'statusCode' => 401
        ]);
});

it('should require email and password for login', function () {
    $response = $this->postJson('/api/auth/login', []);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Validation failed',
            'data' => [
                'email' => ['The email field is required.'],
                'password' => ['The password field is required.']
            ],
            'statusCode' => 422
        ]);
});

// Logout Tests
it('should successfully logout an authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Logout successful',
            'data' => null,
            'statusCode' => 200
        ]);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
    ]);
});

it('should return unauthorized when logging out without a token', function () {
    $response = $this->postJson('/api/auth/logout');

    $response->assertStatus(401);
});

// Forgot Password Tests
it('should send a password reset link to a valid email', function () {
    $user = User::factory()->create();
    Notification::fake();

    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Password reset link sent to your email',
            'data' => null,
            'statusCode' => 200
        ]);

    Notification::assertSentTo($user, QueuedResetPasswordNotification::class);
});

it('should not send a password reset link to an invalid email', function () {
    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'User not found',
            'data' => null,
            'statusCode' => 404
        ]);
});

it('should require an email for forgot password request', function () {
    $response = $this->postJson('/api/auth/forgot-password', []);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Validation failed',
            'data' => [
                'email' => ['The email field is required.']
            ],
            'statusCode' => 422
        ]);
});

it('should validate email format for forgot password request', function () {
    $response = $this->postJson('/api/auth/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Validation failed',
            'data' => [
                'email' => ['The email field must be a valid email address.']
            ],
            'statusCode' => 422
        ]);
});


it('should reset a password', function () {
    // Create and verify a user
    registerUser();
    verifyUser();

    // Fake notifications
    Notification::fake();

    // Request password reset
    $response = test()->post('/api/auth/forgot-password', [
        'email' => 'john@example.com',
    ]);
    $response->assertStatus(200);

    // Get the user
    $user = User::where('email', 'john@example.com')->first();

    // Assert the notification was sent
    Notification::assertSentTo(
        $user,
        QueuedResetPasswordNotification::class
    );

    // Get the token from the notification
    $notification = Notification::sent($user, QueuedResetPasswordNotification::class)->first();
    expect($notification)->not->toBeNull();
    $token = $notification->token;

    // Reset the password
    $response = test()->post('/api/auth/reset-password', [
        'email' => 'john@example.com',
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
        'token' => $token,
    ]);

    $response->assertStatus(200);

    // Verify the new password works
    $response = loginUser('john@example.com', 'newpassword');
    $response->assertStatus(200);
});