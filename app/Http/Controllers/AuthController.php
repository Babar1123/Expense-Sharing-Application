<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthenticationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthenticationService $authenticationService)
    {
    }

    public function register(RegisterRequest $request)
    {
        $payload = $this->authenticationService->register($request->validated());

        return ApiResponse::success('User registered successfully.', $payload);
    }

    public function login(LoginRequest $request)
    {
        $payload = $this->authenticationService->login($request->validated());

        return ApiResponse::success('Login successful.', $payload);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $this->authenticationService->logout($user, $request->bearerToken());

        return ApiResponse::success('Logged out successfully.', []);
    }

    public function me(Request $request)
    {
        return ApiResponse::success('User profile retrieved successfully.', $request->user()->makeHidden(['password']));
    }
}
