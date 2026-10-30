<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccessNotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', ['notices' => $this->query($request)->latest()->paginate(25)]);
    }

    public function read(Request $request, int $notification)
    {
        abort_unless($this->query($request)->where('id', $notification)->update(['read_at' => now(), 'updated_at' => now()]), 404);
        return back()->with('status', 'Notificación leída.');
    }

    public function apiIndex(Request $request)
    {
        return response()->json($this->query($request)->latest()->limit(50)->get(['id', 'kind', 'title', 'body', 'read_at', 'created_at']));
    }

    public function apiRead(Request $request, int $notification)
    {
        abort_unless($this->query($request)->where('id', $notification)->update(['read_at' => now(), 'updated_at' => now()]), 404);
        return response()->json(['message' => 'Notificación leída.']);
    }

    private function query(Request $request)
    {
        $user = $request->user();
        return DB::table('access_notifications')->where('company_id', $user->company_id)->where('recipient_id', $user->id);
    }
}
