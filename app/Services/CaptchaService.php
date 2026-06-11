<?php

namespace App\Services;

use Closure;
use Illuminate\Http\Request;

class CaptchaService
{
    public function ensureChallenge(Request $request, string $formKey): string
    {
        $payload = $request->session()->get($this->sessionKey($formKey));

        if (!is_array($payload) || !isset($payload['question'], $payload['answer'])) {
            return $this->refreshChallenge($request, $formKey);
        }

        return (string) $payload['question'];
    }

    public function refreshChallenge(Request $request, string $formKey): string
    {
        $left = random_int(1, 9);
        $right = random_int(1, 9);

        if (random_int(0, 1) === 1) {
            $question = "Combien font {$left} + {$right} ?";
            $answer = (string) ($left + $right);
        } else {
            if ($left < $right) {
                [$left, $right] = [$right, $left];
            }

            $question = "Combien font {$left} - {$right} ?";
            $answer = (string) ($left - $right);
        }

        $request->session()->put($this->sessionKey($formKey), [
            'question' => $question,
            'answer' => $answer,
        ]);

        return $question;
    }

    public function validationRules(Request $request, string $formKey): array
    {
        return [
            'required',
            'string',
            function (string $attribute, mixed $value, Closure $fail) use ($request, $formKey): void {
                $payload = $request->session()->get($this->sessionKey($formKey));
                $expected = is_array($payload) ? ($payload['answer'] ?? null) : null;

                if ((string) trim((string) $value) !== (string) $expected) {
                    $fail('Le captcha est incorrect.');
                }
            },
        ];
    }

    private function sessionKey(string $formKey): string
    {
        return "captcha.{$formKey}";
    }
}
