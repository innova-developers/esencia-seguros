<?php

namespace App\Application\Auth;

use App\Domain\Services\SSNAuthService;

class SSNLoginUseCase
{
    private SSNAuthService $ssnAuthService;

    public function __construct(SSNAuthService $ssnAuthService)
    {
        $this->ssnAuthService = $ssnAuthService;
    }

    public function __invoke(string $username, string $cia, string $password): ?array
    {
        $result = $this->ssnAuthService->authenticate($username, $cia, $password);

        if ($result && $result['success']) {
            // Cachear el token si la autenticación fue exitosa
            if (isset($result['token']) && isset($result['expiration'])) {
                $this->ssnAuthService->cacheToken($result['token'], $result['expiration'], $username, $cia);
            }
        }

        return $result;
    }
} 