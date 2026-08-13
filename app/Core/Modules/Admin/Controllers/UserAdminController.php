<?php

namespace App\Core\Modules\Admin\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    /**
     * Daftar user platform (FR-006) + filter tipe member.
     */
    public function index(Request $request): View
    {
        $query = User::withCount('tenants')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($memberType = $request->input('member_type')) {
            $query->where('member_type', $memberType);
        }

        return view('admin.users.index', [
            'users' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'member_type']),
            'memberTypes' => User::MEMBER_TYPES,
        ]);
    }

    /**
     * Form buat user manual.
     */
    public function create(): View
    {
        return view('admin.users.form', [
            'user' => null,
            'memberTypes' => User::MEMBER_TYPES,
        ]);
    }

    /**
     * Simpan user baru (dibuat manual oleh superadmin).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_admin' => $data['is_admin'] ?? false,
            'member_type' => $data['member_type'],
            'email_verified_at' => now(), // dibuat manual admin → langsung verified
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "User {$user->name} berhasil dibuat.");
    }

    /**
     * Form edit user.
     */
    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'memberTypes' => User::MEMBER_TYPES,
        ]);
    }

    /**
     * Update data user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->is_admin = $data['is_admin'] ?? false;
        $user->member_type = $data['member_type'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', "User {$user->name} berhasil diperbarui.");
    }

    /**
     * Hapus user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['gagal' => 'Tidak bisa menghapus akun sendiri.']);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', "User {$user->name} berhasil dihapus.");
    }

    /**
     * Validasi bersama untuk store & update.
     */
    protected function validated(Request $request, ?User $user): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'member_type' => ['required', Rule::in(User::MEMBER_TYPES)],
            'is_admin' => ['sometimes', 'boolean'],
        ]);
    }
}
