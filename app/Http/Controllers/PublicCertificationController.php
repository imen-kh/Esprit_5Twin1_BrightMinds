<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PublicCertificationController extends Controller
{
    public function show(Request $request, Certification $certification): View|JsonResponse
    {
        $certification->load(['label', 'product', 'acteur']);

        return $request->expectsJson()
            ? response()->json($certification)
            : view('certifications.show', compact('certification'));
    }

    public function qr(Certification $certification): \Illuminate\Http\Response
    {
        $url = rtrim((string) config('app.qr_public_url'), '/')
            .route('certifications.public.show', $certification, false);

        if (trim((string) config('app.qr_public_url')) === '') {
            $url = request()->getSchemeAndHttpHost().route('certifications.public.show', $certification, false);
        }

        return response((string) QrCode::format('svg')->size(260)->margin(1)->generate($url))
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
