<?php

namespace App\Core\Modules\Admin\Controllers;

use App\Core\Modules\Apps\Models\AppModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppAdminController extends Controller
{
    /**
     * Kelola katalog aplikasi (FR-006).
     */
    public function index(): View
    {
        return view('admin.apps.index', [
            'apps' => AppModel::withCount('subscriptions')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.apps.form', ['app' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateApp($request);

        AppModel::create([
            'slug' => \Illuminate\Support\Str::slug($validated['name']),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price_monthly' => $validated['price_monthly'],
            'status' => $validated['status'],
            'logo' => $validated['logo'] ?? null,
        ]);

        return redirect()->route('admin.apps.index')->with('status', 'app-saved');
    }

    public function edit(AppModel $app): View
    {
        return view('admin.apps.form', ['app' => $app]);
    }

    public function update(Request $request, AppModel $app): RedirectResponse
    {
        $validated = $this->validateApp($request, $app->id);

        // Slug TIDAK diubah saat update — biar referensi SSO/subdomain tetap aman.
        $app->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price_monthly' => $validated['price_monthly'],
            'status' => $validated['status'],
            'logo' => $validated['logo'] ?? $app->logo,
        ]);

        return redirect()->route('admin.apps.index')->with('status', 'app-saved');
    }

    protected function validateApp(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            // Name unique sebagai proxy slug (slug = Str::slug(name)) — mencegah
            // nama dobel → slug dobel → 500 di unique constraint.
            'name' => ['required', 'string', 'max:255', Rule::unique('apps', 'name')->ignore($ignoreId)],
            'description' => ['required', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,coming_soon'],
            'logo' => ['nullable', 'url'],
        ];

        return $request->validate($rules);
    }
}
