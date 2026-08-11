<?php

namespace App\Core\Modules\Admin\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    /**
     * Daftar user platform (FR-006).
     */
    public function index(Request $request): View
    {
        $query = User::withCount('tenants')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return view('admin.users.index', [
            'users' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }
}
