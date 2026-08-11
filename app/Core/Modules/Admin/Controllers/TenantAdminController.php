<?php

namespace App\Core\Modules\Admin\Controllers;

use App\Core\Modules\Tenant\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantAdminController extends Controller
{
    /**
     * Daftar tenant: search + filter status (FR-006).
     */
    public function index(Request $request): View
    {
        $query = Tenant::withCount('subscriptions')
            ->with('createdBy')
            ->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('admin.tenants.index', [
            'tenants' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Detail tenant: subscription + payments (FR-006).
     */
    public function show(Tenant $tenant): View
    {
        $tenant->load([
            'subscriptions.app',
            'payments.subscription.app',
            'users',
            'createdBy',
        ]);

        return view('admin.tenants.show', compact('tenant'));
    }
}
