<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        return view('activity.index', [
            'activity' => ActivityLog::with('actor')
                ->where('company_id', $request->user()->company_id)
                ->latest()->paginate(25),
        ]);
    }
}
