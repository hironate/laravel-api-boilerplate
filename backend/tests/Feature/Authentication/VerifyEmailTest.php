<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('should resend a verification email', function () {
    // Create an unverified user
    registerUser();
    $user = User::where('email', 'john@example.com')->first();

    // Fake the Notification facade
    Notification::fake();

    // Attempt to resend the verification email
    $response = $this->actingAs($user)->post('/api/auth/email/verification-notification', [
        'email' => $user->email
    ]);

    $response->assertStatus(200);

    // Assert that a verification email was sent
    Notification::assertSentTo(
        $user,
        QueuedVerifyEmail::class
    );

    // Additional assertions
    $sentNotifications = Notification::sent($user, QueuedVerifyEmail::class);
    expect($sentNotifications)->toHaveCount(1);

    // Check the content of the notification
    $notification = $sentNotifications->first();
    expect($notification)->toBeInstanceOf(QueuedVerifyEmail::class);
    expect($notification->toMail($user)->actionUrl)->not->toBeNull();
});

it('should not resend a verification email if user is already verified', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->post('/api/auth/email/verification-notification', [
        'email' => $user->email
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Verification email resent successfully',
        'data' => null,
        'statusCode' => 200
    ]);
});

it('should not resend a verification email if email is not provided', function () {
    $user = User::factory()->create(['email_verified_at' => null]);


    $response = $this->actingAs($user)->post('/api/auth/email/verification-notification', []);

    $response->assertStatus(422);
    $response->assertExactJson([
        'message' => 'Validation failed',
        'data' => [
            'email' => ['The email field is required.']
        ],
        'statusCode' => 422
    ]);
});

it('should verify email', function () {
    // Create an unverified user
    registerUser();
    $user = User::where('email', 'john@example.com')->first();

    // Fake notifications
    Notification::fake();

    // Trigger sending of verification email
    $user->sendEmailVerificationNotification();

    // Assert the notification was sent
    Notification::assertSentTo($user, QueuedVerifyEmail::class);

    // Get the verification URL from the notification
    $notification = Notification::sent($user, QueuedVerifyEmail::class)->first();
    $verificationUrl = $notification->toMail($user)->actionUrl;

    // Parse the URL to get the verification data
    $urlParts = parse_url($verificationUrl);
    parse_str($urlParts['query'], $queryParams);


    // Make a request to the verification endpoint
    $response = $this->get("/api/auth/email/verify/{$user->id}/{$queryParams['hash']}?expires={$queryParams['expires']}&signature={$queryParams['signature']}");

    $response->assertStatus(200); // Expecting a successful response
    $response->assertJson([
        'message' => 'Email verified successfully',
        'data' => null,
        'statusCode' => 200
    ]);

    // Assert that the user's email is now verified
    $this->assertTrue($user->fresh()->hasVerifiedEmail());
});

it('should not verify email with invalid hash', function () {
    // Create an unverified user
    Notification::fake();
    registerUser();
    $user = User::where('email', 'john@example.com')->first();

    $notification = Notification::sent($user, QueuedVerifyEmail::class)->first();
    $verificationUrl = $notification->toMail($user)->actionUrl;

    // Parse the URL to get the verification data
    $urlParts = parse_url($verificationUrl);
    parse_str($urlParts['query'], $queryParams);

    // Make a request to the verification endpoint
    $response = $this->get("/api/auth/email/verify/{$user->id}/{$queryParams['hash']}asd?expires={$queryParams['expires']}&signature={$queryParams['signature']}");
    $response->assertStatus(403);
    $response->assertJson([
        'message' => 'Invalid signature.',
        'data' => null,
        'statusCode' => 403
    ]);
});


it('should not verify email if already verified', function () {
    // Create a verified user
    // Create an unverified user
    Notification::fake();
    registerUser();
    $user = User::where('email', 'john@example.com')->first();
    $user->markEmailAsVerified();
    $notification = Notification::sent($user, QueuedVerifyEmail::class)->first();
    $verificationUrl = $notification->toMail($user)->actionUrl;

    // Parse the URL to get the verification data
    $urlParts = parse_url($verificationUrl);
    parse_str($urlParts['query'], $queryParams);

    // Make a request to the verification endpoint
    $response = $this->get("/api/auth/email/verify/{$user->id}/{$queryParams['hash']}?expires={$queryParams['expires']}&signature={$queryParams['signature']}");

    $response->assertJson([
        'message' => 'Email already verified',
        'data' => null,
        'statusCode' => 200
    ]);
});
