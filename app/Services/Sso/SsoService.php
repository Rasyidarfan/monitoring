<?php

namespace App\Services\Sso;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SsoService
{
    protected string $baseUrl;
    protected string $dataUrl;
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('sso.base_url');
        $this->dataUrl = config('sso.data_url');
        $this->clientId = config('sso.client_id');
        $this->clientSecret = config('sso.client_secret');
    }

    /**
     * Get authorization URL
     */
    public function getAuthorizationUrl(?string $state = null): string
    {
        $params = [
            'client_id' => $this->clientId,
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return config('sso.authorize_url') . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for user data
     */
    public function getUserFromCode(string $code): ?array
    {
        try {
            $tokenUrl = config('sso.token_url');

            Log::info('SSO Token Exchange - Starting', [
                'url' => $tokenUrl,
                'code_prefix' => substr($code, 0, 10),
                'client_id' => $this->clientId,
            ]);

            $response = Http::asForm()->post($tokenUrl, [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            Log::info('SSO Token Exchange - Response Received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            // Log the full response body for debugging
            Log::debug('SSO Token Exchange - Full Response Body', [
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('SSO Token Exchange - Parsed Response', [
                    'data_keys' => array_keys((array)$data ?? []),
                    'has_status' => isset($data['status']),
                    'has_data' => isset($data['data']),
                    'has_error' => isset($data['error']),
                ]);

                if ($data['status'] === 'success' && isset($data['data'])) {
                    Log::info('SSO Token Exchange - Success', [
                        'user_id' => $data['data']['user_id'] ?? 'unknown',
                    ]);
                    return $data['data'];
                }

                // Handle API-level errors (INVALID_GRANT, INVALID_CLIENT_SECRET, etc.)
                if (isset($data['error'])) {
                    Log::error('SSO Token Exchange - API Error', [
                        'error_code' => $data['error'],
                        'message' => $data['message'] ?? 'Unknown error',
                    ]);
                    return null;
                }
            }

            // Handle HTTP errors
            $responseBody = $response->json() ?? [];
            Log::error('SSO Token Exchange Failed', [
                'status' => $response->status(),
                'error' => $responseBody['error'] ?? 'Unknown',
                'message' => $responseBody['message'] ?? $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SSO Token Exchange Error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return null;
        }
    }

    /**
     * Get all employees
     */
    public function getAllEmployees(): ?array
    {
        try {
            $response = Http::asForm()->post(
                $this->dataUrl . config('sso.endpoints.employees'),
                ['client_secret' => $this->clientSecret]
            );

            if ($response->successful()) {
                $data = $response->json();

                if (!is_array($data)) {
                    Log::error('SSO Get Employees - Invalid Response Format', [
                        'response_type' => gettype($data),
                    ]);
                    return null;
                }

                return $data;
            }

            // Handle API errors (rate limiting, invalid secret, etc.)
            $responseBody = $response->json() ?? [];
            Log::error('SSO Get Employees Failed', [
                'status' => $response->status(),
                'error' => $responseBody['error'] ?? 'Unknown',
                'message' => $responseBody['message'] ?? 'API request failed',
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SSO Get Employees Error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return null;
        }
    }

    /**
     * Get all roles
     */
    public function getAllRoles(): ?array
    {
        try {
            $response = Http::get($this->dataUrl . config('sso.endpoints.roles'));

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SSO Get Roles Error', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get employees by role
     */
    public function getEmployeesByRole(string $role): ?array
    {
        try {
            $response = Http::asForm()->post(
                $this->dataUrl . config('sso.endpoints.employees_by_role'),
                [
                    'client_secret' => $this->clientSecret,
                    'role' => $role,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();

                if (!is_array($data)) {
                    Log::error('SSO Get Employees By Role - Invalid Response Format', [
                        'response_type' => gettype($data),
                        'role' => $role,
                    ]);
                    return null;
                }

                return $data;
            }

            // Handle API errors (ROLE_NOT_FOUND, invalid secret, rate limiting, etc.)
            $responseBody = $response->json() ?? [];
            Log::error('SSO Get Employees By Role Failed', [
                'status' => $response->status(),
                'error' => $responseBody['error'] ?? 'Unknown',
                'message' => $responseBody['message'] ?? 'API request failed',
                'role' => $role,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SSO Get Employees By Role Error', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'role' => $role,
            ]);
            return null;
        }
    }
}
