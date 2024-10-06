<?php

namespace App\Http\Controllers\Api\Auth;

//use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\GoogleLoginRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Requests\Api\ResendEmailVerificationRequest;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponses;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    use ApiResponses;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Api\LoginRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $request->validated($request->all());

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->unauthorized('Invalid credentials');
        }

        $user = Auth::user();
        if (!$user->hasVerifiedEmail()) {
            Auth::logout();
            return $this->error('Email not verified. Please verify your email before logging in.', null, 403);
        }

        $token = $this->userService->createTokenFromEmail($request->email);
        $user = $this->userService->transformUser(Auth::user());

        return $this->success("Login successful", ['token' => $token, 'user' => $user]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \App\Http\Requests\Api\RegisterRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {
        $request->validated($request->all());

        $user = $this->userService->findOrCreateUser($request->only('name', 'email', 'password'));
        event(new Registered($user));

        return $this->success("Registration successful", $user);
    }

    /**
     * Logout the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): \Illuminate\Http\JsonResponse
    {
        Auth::user()->tokens()->delete();
        return $this->success("Logout successful", null);
    }

    /**
     * Verify the user's email address.
     *
     * @param  int  $id
     * @param  string  $hash
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail($id, $hash): \Illuminate\Http\JsonResponse
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->error('Invalid verification link', 400);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success('Email already verified', null);
        }

        $user->markEmailAsVerified();

        return $this->success('Email verified successfully', null);
    }

    /**
     * Resend the email verification notification.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationEmail(ResendEmailVerificationRequest $request): \Illuminate\Http\JsonResponse
    {
        $request->validated($request->all());

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->notFound('User not found', 404);
        }
        $user->sendEmailVerificationNotification();

        return $this->success('Verification email resent successfully', null);
    }

    public function forgotPassword(ForgotPasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        $request->validated($request->all());

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::INVALID_USER) {
            return $this->notFound('User not found', null);
        }

        if ($status === Password::RESET_THROTTLED) {
            return $this->tooManyRequests('Too many requests');
        }

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success('Password reset link sent to your email', null);
        } else {
            return $this->serverError('Failed to send password reset email', 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        $request->validated($request->all());

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::INVALID_USER) {
            return $this->notFound('User not found', 404);
        }

        if ($status === Password::INVALID_TOKEN) {
            return $this->badRequest('Invalid token', 400);
        }

        if ($status === Password::PASSWORD_RESET) {
            return $this->success('Password reseted successfully');
        } else {
            return $this->badRequest('Failed to reset password', 400);
        }
    }


    public function googleLogin(GoogleLoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $request->validated($request->all());

        $provider = 'google';
        $driver = Socialite::driver($provider);
        $socialUserObject = $driver->userFromToken($request->token);

        $userData = [
            'name' => $socialUserObject->getName(),
            'email' => $socialUserObject->getEmail(),
            'google_id' => $socialUserObject->getId(),
        ];

        $user = $this->userService->findOrCreateUser($userData);
        $token = $this->userService->createTokenFromEmail($user->email);
        $user = $this->userService->transformUser($user);

        return $this->success("Login successful", ['token' => $token, 'user' => $user]);
    }
}
