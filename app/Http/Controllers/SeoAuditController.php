<?php

namespace App\Http\Controllers;

use App\Models\SeoAudit;
use App\Models\Website;
use App\Services\SeoScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SeoAuditController extends Controller
{
    public function index(): View
    {
        return view('seo-audits.index', [
            'audits' => SeoAudit::with('website')->latest()->paginate(15),
        ]);
    }

    public function store(Website $website, SeoScanner $scanner): RedirectResponse
    {
        $audit = $scanner->scan($website);

        return redirect()->route('websites.show', $website)->with(
            $audit->raw_error ? 'error' : 'success',
            $audit->raw_error ?: 'SEO audit completed.'
        );
    }
}
