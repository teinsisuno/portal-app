<?php

namespace App\Core\Modules\Auth\Controllers;

use App\Core\Modules\Tenant\Models\Tenant;
use App\Core\Modules\Tenant\Models\TenantUser;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Tampilkan form registrasi.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Proses registrasi: buat user + tenant (status pending) + pivot owner.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Auto-create tenant untuk user baru (status pending, slug unik)
        $tenant = Tenant::create([
            'name' => $request->name,
            'slug' => $this->uniqueSlug($request->name),
            'email' => $request->email,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard'))
            ->with('status', 'verification-notice');
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
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
