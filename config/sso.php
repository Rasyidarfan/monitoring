<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SSO BPS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SSO BPS authentication service
    |
    */

    'base_url' => env('SSO_BASE_URL', 'https://sso.bps9702.com/sso/'),
    'data_url' => env('SSO_DATA_URL', 'https://sso.bps9702.com/api/'),

    'client_id' => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),

    'login_url' => env('SSO_LOGIN_URL', 'https://sso.bps9702.com/login'),
    'authorize_url' => env('SSO_AUTHORIZE_URL', 'https://sso.bps9702.com/sso/authorize'),
    'token_url' => env('SSO_TOKEN_URL', 'https://sso.bps9702.com/sso/token'),
    'callback_url' => env('SSO_CALLBACK_URL', 'http://localhost:8000/auth/callback'),

    /*
    |--------------------------------------------------------------------------
    | SSO Data Endpoints
    |--------------------------------------------------------------------------
    */

    'endpoints' => [
        'employees' => 'employees',
        'roles' => 'roles',
        'employees_by_role' => 'employees/by-role',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles Configuration
    |--------------------------------------------------------------------------
    |
    | Define which SSO roles can perform specific actions
    |
    */

    'roles' => [
        'admin' => ['admin', 'superadmin'],  // Can manage activities & upload
        'editor' => ['editor', 'ppl', 'pml'], // Can upload data
        'viewer' => ['user', 'viewer'],       // Can only view (default)
    ],

];
