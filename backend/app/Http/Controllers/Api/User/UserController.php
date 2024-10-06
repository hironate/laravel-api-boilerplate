<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponses;

class UserController extends Controller
{
    use ApiResponses;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $user = $this->userService->transformUser($user);
        return $this->success('User details', $user);
    }
}
