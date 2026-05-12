<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request, string $siteKey): JsonResponse
    {
        // Layer 1: honeypot — return fake success without storing if filled
        if ($request->filled('website_url')) {
            return $this->fakeSuccess();
        }

        // Layer 2: Cloudflare Turnstile — return fake success on miss to avoid alerting bots
        $turnstileToken = $request->input('cf_turnstile_response');
        if (! $turnstileToken || ! $this->verifyTurnstile($turnstileToken, $request->ip())) {
            Log::info('Turnstile verification failed', [
                'site_key' => $siteKey,
                'ip'       => $request->ip(),
            ]);
            return $this->fakeSuccess();
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'company'      => 'nullable|string|max:255',
            'message'      => 'required|string|max:5000',
            'budget_range' => 'nullable|string|max:100',
            'source'       => 'nullable|string|max:255',
        ]);

        $submission = ContactSubmission::create(array_merge(
            $validated,
            ['site_key' => $siteKey]
        ));

        try {
            Mail::raw(
                "New contact submission from {$validated['name']} ({$validated['email']})\n\n" .
                'Company: ' . ($validated['company'] ?? 'N/A') . "\n" .
                'Budget: '  . ($validated['budget_range'] ?? 'N/A') . "\n" .
                'Source: '  . ($validated['source'] ?? 'N/A') . "\n\n" .
                "Message:\n{$validated['message']}",
                function ($message) use ($siteKey) {
                    $message->to('hello@zeplow.com')->subject("New Lead — {$siteKey}");
                }
            );
        } catch (\Throwable $e) {
            Log::error('Contact email notification failed', [
                'submission_id' => $submission->id,
                'error'         => $e->getMessage(),
            ]);
            // Don't fail the response — submission is stored, email is secondary
        }

        return $this->fakeSuccess();
    }

    private function fakeSuccess(): JsonResponse
    {
        return response()->json([
            'status'  => 'received',
            'message' => 'Thank you. We\'ll be in touch within 24 hours.',
        ]);
    }

    private function verifyTurnstile(string $token, ?string $ip): bool
    {
        $secret = config('services.cloudflare.turnstile_secret');

        // Skip verification when no secret is configured (dev/staging).
        if (! $secret) {
            return true;
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) $response->json('success', false);
        } catch (\Throwable $e) {
            Log::error('Turnstile verification request failed', ['error' => $e->getMessage()]);
            // On network failure, fail open — better than blocking real users
            return true;
        }
    }
}
