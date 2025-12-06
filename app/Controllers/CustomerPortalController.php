<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\InputFilter;
use App\Services\CustomerPortalAuthService;
use App\Services\CustomerPortalService;

final class CustomerPortalController
{
    public function __construct(
        private CustomerPortalAuthService $authService,
        private CustomerPortalService $portalService
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, account?:array<string, mixed>, errors?:array<int, string>}
     */
    public function login(array $input): array
    {
        $rawEmail = $input['email'] ?? '';
        $email = InputFilter::email($rawEmail) ?? InputFilter::lowercase($rawEmail, 190);
        $password = InputFilter::string($input['password'] ?? '', 255, false, false);
        $remember = InputFilter::bool($input['remember'] ?? null);

        return $this->authService->login($email, $password, $remember, $this->resolveClientIp());
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, token?:string, errors?:array<int, string>}
     */
    public function invite(array $input): array
    {
        $customerId = InputFilter::int($input['customer_id'] ?? 0, 1, PHP_INT_MAX, 0);
        $email = InputFilter::email($input['email'] ?? '') ?? InputFilter::lowercase($input['email'] ?? '', 190);

        return $this->authService->createInvitation($customerId, $email);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function completeInvitation(array $input): array
    {
        $token = InputFilter::string($input['token'] ?? '', 128);
        $password = InputFilter::string($input['password'] ?? '', 255, false, false);

        return $this->authService->completeInvitation($token, $password);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function updatePassword(int $accountId, array $input): array
    {
        $current = InputFilter::string($input['current_password'] ?? '', 255, false, false);
        $new = InputFilter::string($input['new_password'] ?? '', 255, false, false);

        return $this->authService->updatePassword($accountId, $current, $new);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function createPaymentRequest(int $portalAccountId, int $customerId, array $input): array
    {
        return $this->portalService->createPaymentRequest($portalAccountId, $customerId, $input);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function createSupportRequest(int $customerId, int $portalAccountId, array $input): array
    {
        return $this->portalService->createSupportRequest($customerId, $portalAccountId, $input);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success:bool, message:string, errors?:array<int, string>}
     */
    public function createProductRequest(int $customerId, int $portalAccountId, array $input): array
    {
        return $this->portalService->createProductRequest($customerId, $portalAccountId, $input);
    }

    public function logout(): void
    {
        $this->authService->logout();
    }

    private function resolveClientIp(): ?string
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }

            $value = (string) $_SERVER[$key];
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $parts = array_map('trim', explode(',', $value));
                $value = $parts[0] ?? '';
            }

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
