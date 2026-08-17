@php
    $siteKey = (string) config('services.recaptcha.site_key', '');
    $formId = (string) ($formId ?? '');
    $action = (string) ($action ?? 'form_submit');
    $inputName = (string) ($inputName ?? 'recaptcha_token');
@endphp

<input type="hidden" name="{{ $inputName }}" value="{{ old($inputName, '') }}">

@if($siteKey !== '' && $formId !== '')
    <script src="https://www.google.com/recaptcha/api.js?render={{ $siteKey }}"></script>
    <script>
        (function () {
            const form = document.getElementById('{{ $formId }}');
            if (!form) {
                return;
            }

            let isSubmitting = false;

            form.addEventListener('submit', function (event) {
                if (isSubmitting) {
                    return;
                }

                if (typeof grecaptcha === 'undefined') {
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
                            isSubmitting = true;
                            form.submit();
                        });
                });
            });
        })();
    </script>
@endif
