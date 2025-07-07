<?php

namespace App\Application\Auth;

use App\Domain\Models\User;
use App\Domain\Services\AuthService;

class LoginUserUseCase
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function __invoke(string $email, string $password): ?User
    {
        return $this->authService->attemptLogin($email, $password);
    }
} 