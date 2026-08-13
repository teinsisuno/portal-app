<?php

namespace App\Core\Modules\Auth\Controllers\Api;

use App\Core\Modules\Tenant\Models\Tenant;
use App\Core\Modules\Tenant\Models\TenantUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    /**
     * Registrasi API: buat user + tenant (pending) + pivot owner.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'email' => $validated['email'],
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        event(new Registered($user));

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil. Cek email untuk verifikasi.',
            'user' => $user,
            'tenant' => $tenant,
            'token' => $token,
        ], 201);
    }

    /**
     * Login API: verifikasi kredensial, keluarkan Bearer token (Sanctum).
     * Rate limited: 5x/menit.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Email atau password salah.'], 422);
        }

        // Verifikasi email wajib sebelum login penuh (PRD §5.4 / Sprint 1).
        // Token hanya diberikan setelah email terverifikasi.
        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email belum diverifikasi. Cek inbox untuk link verifikasi.',
            ], 403);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    /**
     * Logout API: hapus token aktif.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    /**
     * Verifikasi email via id + hash (hash = sha1 email, sama dengan link web).
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'hash' => ['required', 'string'],
        ]);

        $user = User::find($request->integer('id'));

        if (! $user || ! hash_equals((string) $user->getEmailForVerification(), (string) $request->input('hash'))) {
            return response()->json(['message' => 'Link verifikasi tidak valid.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah terverifikasi.']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email berhasil diverifikasi.']);
    }

    /**
     * Kirim ulang link verifikasi (butuh auth).
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json(['message' => 'Link verifikasi baru sudah dikirim.']);
    }

    /**
     * Kirim link reset password. Selalu 200 (hindari user enumeration).
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Link reset password sudah dikirim ke email kamu.']);
    }

    /**
     * Proses reset password via token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => 'Password berhasil direset. Silakan login.']);
    }

    /**
     * Buat slug unik dari nama tenant (tambah suffix angka kalau duplikat).
     */
    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $i = 1;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
