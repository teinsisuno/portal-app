<?php

namespace App\Core\Modules\Apps\Controllers;

use App\Core\Modules\Apps\Models\AppModel;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AppsController extends Controller
{
    /**
     * Katalog aplikasi (FR-002).
     */
    public function index(): View
    {
        return view('apps.index', [
            'apps' => AppModel::orderBy('name')->get(),
        ]);
    }
}
