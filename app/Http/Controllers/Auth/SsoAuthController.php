<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Sso\SsoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SsoAuthController extends Controller
{
    public function __construct(
        protected SsoService $ssoService
    ) {}

    /**
     * Redirect to SSO login
     */
    public function redirectToSso(Request $request): RedirectResponse
    {
        // Generate CSRF state token
        $state = Str::random(40);
        $request->session()->put('sso_state', $state);

        // Redirect to SSO authorization URL
        $authUrl = $this->ssoService->getAuthorizationUrl($state);

        return redirect()->away($authUrl);
    }

    /**
     * Handle SSO callback
     */
    public function handleCallback(Request $request): RedirectResponse
    {
        // Validate state token (CSRF protection)
        $sessionState = $request->session()->pull('sso_state');
        $requestState = $request->query('state');

        if ($sessionState && $requestState !== $sessionState) {
            Log::warning('SSO State Mismatch', [
                'session' => $sessionState,
                'request' => $requestState,
            ]);

            return redirect()->route('login')
                ->withErrors(['sso' => 'Invalid state parameter. Please try again.']);
        }

        // Get authorization code
        $code = $request->query('code');

        if (!$code) {
            return redirect()->route('login')
                ->withErrors(['sso' => 'No authorization code received.']);
        }

        // Exchange code for user data
        $ssoUserData = $this->ssoService->getUserFromCode($code);

        if (!$ssoUserData) {
            return redirect()->route('login')
                ->withErrors(['sso' => 'Failed to authenticate with SSO.']);
        }

        // Create or update user
        $user = $this->findOrCreateUser($ssoUserData);

        // Login user
        Auth::login($user, true);

        // Redirect to intended page or dashboard
        return redirect()->intended(route('dashboard'))
            ->with('success', 'Berhasil login sebagai ' . $user->name);
    }

    /**
     * Find or create user from SSO data
     */
    protected function findOrCreateUser(array $ssoData): User
    {
        // Try to find user by SSO user_id
        $user = User::where('sso_user_id', $ssoData['user_id'])->first();

        // If not found, try by NIP
        if (!$user && !empty($ssoData['nip_9'])) {
            $user = User::where('nip_9', $ssoData['nip_9'])->first();
        }

        // Create or update
        if ($user) {
            $user->update($this->mapSsoDataToUser($ssoData));
        } else {
            $user = User::create($this->mapSsoDataToUser($ssoData));
        }

        return $user->fresh();
    }

    /**
     * Map SSO data to user model attributes
     */
    protected function mapSsoDataToUser(array $ssoData): array
    {
        return [
            'sso_user_id' => $ssoData['user_id'],
            'name' => $ssoData['name'],
            'nip_9' => $ssoData['nip_9'] ?? null,
            'nip_18' => $ssoData['nip_18'] ?? null,
            'email' => $ssoData['email'] ?? null,
            'sso_roles' => $ssoData['roles'] ?? [],
            'last_sso_sync_at' => now(),
        ];
    }

    /**
     * Handle logout
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard')
            ->with('success', 'Anda telah berhasil logout.');
    }
}
