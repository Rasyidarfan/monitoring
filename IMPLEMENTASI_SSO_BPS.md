# Implementasi SSO BPS untuk Laravel Monitoring Dashboard

## 📋 Overview

Integrasi OAuth 2.0 dengan SSO BPS (sso.bps9702.com) untuk autentikasi pegawai BPS di aplikasi monitoring.

---

## 🔐 SSO Configuration

### Environment Variables (.env)

```env
# SSO BPS Configuration
SSO_BASE_URL=https://sso.bps9702.com/v1/
SSO_DATA_URL=https://sso.bps9702.com/api/v1/
SSO_CLIENT_ID=advjqihy
SSO_CLIENT_SECRET=Y2fJD6T1ycJSbheLFYHaNMsDZhFTkWBG
SSO_LOGIN_URL=https://sso.bps9702.com/login
SSO_AUTHORIZE_URL=https://sso.bps9702.com/api/v1/authorize
SSO_TOKEN_URL=https://sso.bps9702.com/api/v1/token
SSO_CALLBACK_URL=http://localhost:8000/auth/callback
```

### Config File

Buat file konfigurasi SSO:

```php
// config/sso.php

return [
    'base_url' => env('SSO_BASE_URL', 'https://sso.bps9702.com/v1/'),
    'data_url' => env('SSO_DATA_URL', 'https://sso.bps9702.com/api/v1/'),
    'client_id' => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'login_url' => env('SSO_LOGIN_URL', 'https://sso.bps9702.com/login'),
    'authorize_url' => env('SSO_AUTHORIZE_URL', 'https://sso.bps9702.com/api/v1/authorize'),
    'token_url' => env('SSO_TOKEN_URL', 'https://sso.bps9702.com/api/v1/token'),
    'callback_url' => env('SSO_CALLBACK_URL', 'http://localhost:8000/auth/callback'),

    // Endpoints
    'endpoints' => [
        'employees' => 'data/employees',
        'roles' => 'data/roles',
        'employees_by_role' => 'data/employees/by-role',
    ],
];
```

---

## 🗄️ Database Schema Update

### Migration: Add SSO Fields to Users Table

```php
// database/migrations/xxxx_add_sso_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // SSO User Data
            $table->string('sso_user_id')->unique()->nullable()->after('id');
            $table->string('nip_9', 9)->unique()->nullable()->after('sso_user_id');
            $table->string('nip_18', 18)->unique()->nullable()->after('nip_9');

            // Make email nullable (SSO provides it)
            $table->string('email')->nullable()->change();

            // Store roles as JSON
            $table->json('sso_roles')->nullable()->after('remember_token');

            // Last SSO sync timestamp
            $table->timestamp('last_sso_sync_at')->nullable();

            // Indexes
            $table->index('sso_user_id');
            $table->index('nip_9');
            $table->index('nip_18');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['sso_user_id']);
            $table->dropIndex(['nip_9']);
            $table->dropIndex(['nip_18']);

            $table->dropColumn([
                'sso_user_id',
                'nip_9',
                'nip_18',
                'sso_roles',
                'last_sso_sync_at',
            ]);
        });
    }
};
```

---

## 🏗️ Struktur Implementasi

### 1. Service Layer

```
app/Services/
└── Sso/
    ├── SsoService.php           # Main SSO service
    ├── SsoAuthService.php       # Authentication flow
    └── SsoDataService.php       # Data endpoints (employees, roles)
```

### 2. Controllers

```
app/Http/Controllers/Auth/
├── SsoAuthController.php        # Handle login & callback
└── LogoutController.php         # Handle logout
```

### 3. Middleware

```
app/Http/Middleware/
├── EnsureSsoAuthenticated.php   # Check SSO auth
└── SyncSsoUserData.php          # Auto-sync user data
```

### 4. Models

```
app/Models/
└── User.php                     # Extended dengan SSO fields
```

---

## 💻 Implementation Code

### 1. SSO Service

```php
// app/Services/Sso/SsoService.php

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
            $response = Http::asForm()->post(config('sso.token_url'), [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success' && isset($data['data'])) {
                    return $data['data'];
                }
            }

            Log::error('SSO Token Exchange Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SSO Token Exchange Error', [
                'message' => $e->getMessage(),
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
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SSO Get Employees Error', [
                'message' => $e->getMessage(),
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
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SSO Get Employees By Role Error', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
```

### 2. Auth Controller

```php
// app/Http/Controllers/Auth/SsoAuthController.php

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
        return redirect()->intended(route('dashboard'));
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

        // Redirect to SSO logout (optional)
        // return redirect()->away(config('sso.login_url') . '/logout');

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil logout.');
    }
}
```

### 3. User Model Update

```php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'sso_user_id',
        'nip_9',
        'nip_18',
        'sso_roles',
        'last_sso_sync_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'sso_roles' => 'array',
            'last_sso_sync_at' => 'datetime',
        ];
    }

    /**
     * Check if user has specific SSO role
     */
    public function hasSsoRole(string $role): bool
    {
        return in_array($role, $this->sso_roles ?? []);
    }

    /**
     * Check if user has any of the given SSO roles
     */
    public function hasAnySsoRole(array $roles): bool
    {
        return !empty(array_intersect($roles, $this->sso_roles ?? []));
    }

    /**
     * Get formatted NIP
     */
    public function getFormattedNipAttribute(): string
    {
        return $this->nip_18 ?? $this->nip_9 ?? '-';
    }

    /**
     * Check if SSO data needs refresh
     */
    public function needsSsoSync(): bool
    {
        if (!$this->last_sso_sync_at) {
            return true;
        }

        // Sync every 24 hours
        return $this->last_sso_sync_at->addDay()->isPast();
    }
}
```

### 4. Middleware

```php
// app/Http/Middleware/EnsureSsoAuthenticated.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSsoAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('sso.login');
        }

        // Check if user has SSO ID
        if (!Auth::user()->sso_user_id) {
            Auth::logout();
            return redirect()->route('sso.login')
                ->withErrors(['sso' => 'Invalid SSO authentication. Please login again.']);
        }

        return $next($request);
    }
}
```

---

## 🛣️ Routes

```php
// routes/web.php

use App\Http\Controllers\Auth\SsoAuthController;

// SSO Authentication Routes
Route::prefix('auth')->name('sso.')->group(function () {
    Route::get('login', [SsoAuthController::class, 'redirectToSso'])
        ->name('login');

    Route::get('callback', [SsoAuthController::class, 'handleCallback'])
        ->name('callback');

    Route::post('logout', [SsoAuthController::class, 'logout'])
        ->name('logout');
});

// Protected routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');

    // ... other routes
});

// Redirect /login ke SSO
Route::get('/login', function () {
    return redirect()->route('sso.login');
})->name('login');
```

---

## 🎨 Login View

```blade
{{-- resources/views/auth/login.blade.php --}}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard Monitoring BPS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Dashboard Monitoring</h1>
                <p class="text-gray-600 mt-2">BPS Kabupaten Jayawijaya</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <a href="{{ route('sso.login') }}"
               class="w-full flex items-center justify-center px-4 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
                Login dengan SSO BPS
            </a>

            <p class="text-xs text-gray-500 text-center mt-6">
                Gunakan akun SSO BPS Anda untuk login
            </p>
        </div>
    </div>
</body>
</html>
```

---

## 🔄 Authentication Flow Diagram

```
User                  Laravel App              SSO BPS
  |                        |                       |
  |-- Click "Login" ------>|                       |
  |                        |                       |
  |                        |-- Redirect to ------->|
  |                        |   authorize URL       |
  |                        |   (with client_id)    |
  |                        |                       |
  |<--------------------- Redirect to SSO login ---|
  |                        |                       |
  |-- Enter credentials -->|                       |
  |                        |                       |
  |                        |<-- Redirect to -------|
  |                        |    callback URL       |
  |                        |    (with code)        |
  |                        |                       |
  |                        |-- POST token URL ---->|
  |                        |   (code + secret)     |
  |                        |                       |
  |                        |<-- User data ---------|
  |                        |   (JSON response)     |
  |                        |                       |
  |                        |-- Create/Update ----->|
  |                        |   User in DB          |
  |                        |                       |
  |<-- Redirect to --------|                       |
  |    Dashboard           |                       |
```

---

## 🧪 Testing

### Manual Testing Steps

1. **Test Login Flow**
   - Navigate to `http://localhost:8000`
   - Click "Login dengan SSO BPS"
   - Should redirect to `sso.bps9702.com/login`
   - Enter credentials
   - Should redirect back to `/auth/callback`
   - Should be logged in and redirected to dashboard

2. **Test User Creation**
   - Check database `users` table
   - Verify SSO fields are populated correctly
   - Check `sso_roles` JSON field

3. **Test Protected Routes**
   - Logout
   - Try to access protected route directly
   - Should redirect to SSO login

### Unit Tests

```php
// tests/Feature/SsoAuthTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\Sso\SsoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class SsoAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_to_sso_login(): void
    {
        $response = $this->get('/auth/login');

        $response->assertRedirect();
        $response->assertRedirectContains('sso.bps9702.com');
    }

    public function test_callback_creates_new_user(): void
    {
        // Mock SSO response
        Http::fake([
            'sso.bps9702.com/api/v1/token' => Http::response([
                'status' => 'success',
                'data' => [
                    'user_id' => '123456',
                    'name' => 'John Doe',
                    'nip_9' => '123456789',
                    'nip_18' => '199001011234567890',
                    'email' => 'john@example.com',
                    'roles' => ['user'],
                ],
            ], 200),
        ]);

        $response = $this->get('/auth/callback?code=test_code');

        $this->assertDatabaseHas('users', [
            'sso_user_id' => '123456',
            'name' => 'John Doe',
            'nip_9' => '123456789',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_callback_updates_existing_user(): void
    {
        $user = User::factory()->create([
            'sso_user_id' => '123456',
            'name' => 'Old Name',
        ]);

        Http::fake([
            'sso.bps9702.com/api/v1/token' => Http::response([
                'status' => 'success',
                'data' => [
                    'user_id' => '123456',
                    'name' => 'New Name',
                    'nip_9' => '123456789',
                    'nip_18' => '199001011234567890',
                    'email' => 'john@example.com',
                    'roles' => ['admin'],
                ],
            ], 200),
        ]);

        $this->get('/auth/callback?code=test_code');

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals(['admin'], $user->sso_roles);
    }
}
```

---

## 🚀 Deployment Notes

### Production Environment

```env
# .env.production

SSO_CALLBACK_URL=https://monitoring.bps9702.com/auth/callback
```

### Callback URL Registration

Pastikan callback URL sudah terdaftar di SSO BPS:
- Development: `http://localhost:8000/auth/callback`
- Production: `https://monitoring.bps9702.com/auth/callback`

### Security Checklist

- ✅ HTTPS di production (wajib untuk OAuth)
- ✅ Validate state parameter (CSRF protection)
- ✅ Store client_secret di .env (jangan hardcode)
- ✅ Implement rate limiting untuk auth routes
- ✅ Log semua auth attempts
- ✅ Session security (secure, httponly cookies)

---

## 📊 Role-Based Access Control (RBAC)

### Define Permissions

```php
// app/Policies/ActivityPolicy.php

namespace App\Policies;

use App\Models\User;
use App\Models\Activity;

class ActivityPolicy
{
    /**
     * Determine if user can view any activities
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view
        return true;
    }

    /**
     * Determine if user can create activities
     */
    public function create(User $user): bool
    {
        // Only admin role can create
        return $user->hasSsoRole('admin');
    }

    /**
     * Determine if user can delete activities
     */
    public function delete(User $user, Activity $activity): bool
    {
        return $user->hasSsoRole('admin');
    }

    /**
     * Determine if user can import data
     */
    public function import(User $user): bool
    {
        return $user->hasAnySsoRole(['admin', 'editor']);
    }
}
```

### Use in Controller

```php
public function store(Request $request)
{
    $this->authorize('create', Activity::class);

    // ... create activity
}
```

### Use in Blade

```blade
@can('create', App\Models\Activity::class)
    <button>Tambah Kegiatan</button>
@endcan
```

---

## 🔄 Auto-Sync User Data (Optional)

Untuk sinkronisasi otomatis data pegawai dari SSO:

```php
// app/Console/Commands/SyncSsoUsers.php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Sso\SsoService;
use Illuminate\Console\Command;

class SyncSsoUsers extends Command
{
    protected $signature = 'sso:sync-users';
    protected $description = 'Sync all users data from SSO';

    public function __construct(
        protected SsoService $ssoService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Fetching employees from SSO...');

        $employees = $this->ssoService->getAllEmployees();

        if (!$employees) {
            $this->error('Failed to fetch employees from SSO.');
            return 1;
        }

        $count = 0;

        foreach ($employees as $employee) {
            User::updateOrCreate(
                ['sso_user_id' => $employee['user_id']],
                [
                    'name' => $employee['name'],
                    'nip_9' => $employee['nip_9'] ?? null,
                    'nip_18' => $employee['nip_18'] ?? null,
                    'email' => $employee['email'] ?? null,
                    'sso_roles' => $employee['roles'] ?? [],
                    'last_sso_sync_at' => now(),
                ]
            );
            $count++;
        }

        $this->info("Successfully synced {$count} users.");
        return 0;
    }
}
```

Schedule command:

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule): void
{
    $schedule->command('sso:sync-users')->daily();
}
```

---

## 📝 Checklist Implementasi

### Phase 1: Setup
- ✅ Update .env dengan SSO config
- ✅ Create config/sso.php
- ✅ Run migration untuk SSO fields
- ✅ Install HTTP client (included in Laravel)

### Phase 2: Core Implementation
- ⬜ Create SsoService
- ⬜ Create SsoAuthController
- ⬜ Update User model
- ⬜ Setup routes

### Phase 3: UI
- ⬜ Create login view
- ⬜ Update navigation dengan logout button
- ⬜ Add user info display

### Phase 4: Testing
- ⬜ Manual testing
- ⬜ Unit tests
- ⬜ Integration tests

### Phase 5: Production
- ⬜ Register callback URL di SSO
- ⬜ Update production .env
- ⬜ Test di production

---

**Dibuat**: 2026-02-16
**Versi**: 1.0.0

---
