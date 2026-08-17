@php
    $siteKey = (string) config('services.recaptcha.site_key', '');
    $formId = (string) ($formId ?? '');
    $action = (string) ($action ?? 'form_submit');
    $inputName = (string) ($inputName ?? 'recaptcha_token');
@endphp

<input type="hidden" name="{{ $inputName }}" value="{{ old($inputName, '') }}">
<p id="{{ $formId }}-recaptcha-error" class="text-red-500 text-xs mt-1 hidden"></p>

@if($siteKey !== '' && $formId !== '')
    <script src="https://www.google.com/recaptcha/api.js?render={{ $siteKey }}"></script>
    <script>
        (function () {
            const form = document.getElementById('{{ $formId }}');
            const errorEl = document.getElementById('{{ $formId }}-recaptcha-error');
            if (!form) {
                return;
            }

            let isSubmitting = false;

            form.addEventListener('submit', function (event) {
                if (isSubmitting) {
                    return;
                }

                if (errorEl) {
                    errorEl.classList.add('hidden');
                    errorEl.textContent = '';
                }

                if (typeof grecaptcha === 'undefined') {
                    event.preventDefault();
                    if (errorEl) {
                        errorEl.textContent = 'Impossible de charger Google reCAPTCHA. Vérifiez la connexion réseau, le domaine autorisé et vos bloqueurs de scripts.';
                        errorEl.classList.remove('hidden');
                    }
                    return;
                }

                event.preventDefault();

                grecaptcha.ready(function () {
                    grecaptcha.execute('{{ $siteKey }}', { action: '{{ $action }}' })
                        .then(function (token) {
                            const tokenField = form.querySelector('input[name="{{ $inputName }}"]');
                            if (tokenField) {
                                tokenField.value = token;
                            }

                            isSubmitting = true;
                            form.submit();
                        })
                        .catch(function () {
                            if (errorEl) {
                                errorEl.textContent = 'La génération du token reCAPTCHA a échoué. Veuillez réessayer.';
                                errorEl.classList.remove('hidden');
                            }
                        });
                });
            });
        })();
    </script>
@else
    @if($formId !== '')
        <p class="text-red-500 text-xs mt-1">reCAPTCHA n'est pas configuré sur le serveur (RECAPTCHA_SITE_KEY manquante).</p>
    @endif
@endif
