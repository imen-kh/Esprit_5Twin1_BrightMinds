<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(20);
        $layout = Auth::user()->isAdmin() ? 'layouts.back' : 'layouts.front';

        return view('notifications.index', compact('notifications', 'layout'));
    }

    public function marquerLue(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return request()->expectsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Notification marquée comme lue.');
    }

    public function toutMarquerLu()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return request()->expectsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    public function lire(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $url = (string) ($notification->data['url'] ?? '');

        return str_starts_with($url, url('/'))
            ? redirect()->to($url)
            : redirect()->route('reclamations.index');
    }

    public function toutLire(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marquées comme lues.');
    }
}
