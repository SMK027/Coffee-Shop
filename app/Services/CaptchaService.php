<?php

namespace App\Services;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CaptchaService
{
    public function validationRules(Request $request, string $formKey): array
    {
        $expectedAction = $this->actionName($formKey);

        return [
            'required',
            'string',
            function (string $attribute, mixed $value, Closure $fail) use ($request, $expectedAction): void {
                $siteKey = (string) config('services.recaptcha.site_key', '');
                $secretKey = (string) config('services.recaptcha.secret_key', '');
                $minScore = (float) config('services.recaptcha.min_score', 0.5);

                if ($siteKey === '' || $secretKey === '') {
                    $fail('Le service reCAPTCHA est indisponible.');
                    return;
                }

                $token = trim((string) $value);
                if ($token === '') {
                    $fail('La validation reCAPTCHA est requise.');
                    return;
                }

                try {
                    $response = Http::asForm()
                        ->timeout(8)
                        ->post('https://www.google.com/recaptcha/api/siteverify', [
                            'secret' => $secretKey,
                            'response' => $token,
                            'remoteip' => $request->ip(),
                        ]);
                } catch (\Throwable) {
                    $fail('La validation reCAPTCHA a échoué. Veuillez réessayer.');
                    return;
                }

                $payload = $response->json();
                if (! is_array($payload) || ! ($payload['success'] ?? false)) {
                    $fail('La validation reCAPTCHA a échoué. Veuillez réessayer.');
                    return;
                }

                if (($payload['action'] ?? null) !== $expectedAction) {
                    $fail('La validation reCAPTCHA est invalide.');
                    return;
                }

                $score = (float) ($payload['score'] ?? 0.0);
                if ($score < $minScore) {
                    $fail('La validation reCAPTCHA a été rejetée. Veuillez réessayer.');
                }
            },
        ];
    }

    public function siteKey(): string
    {
        return (string) config('services.recaptcha.site_key', '');
    }

    private function actionName(string $formKey): string
    {
        return preg_replace('/[^a-z0-9_]/', '_', strtolower($formKey)) ?: 'form_submit';
    }
}
