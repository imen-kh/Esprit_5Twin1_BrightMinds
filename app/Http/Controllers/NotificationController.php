<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Notifications en base (réclamations, rappels) : lecture puis redirection vers la page concernée.
 */
class NotificationController extends Controller
{
    public function lire(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = (string) ($notification->data['url'] ?? '');

        // Seules les adresses de l'application sont suivies
        return str_starts_with($url, url('/')) ? redirect()->to($url) : redirect()->route('reclamations.index');
    }

    public function toutLire(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marquées comme lues.');
    }
}
