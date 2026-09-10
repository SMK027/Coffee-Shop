@php
    $widgetId = (string) ($widgetId ?? 'supervisor-security-key');
    $optionsUrl = route('supervisor-security-key.options');
@endphp

<div class="border border-stone-200 rounded-xl p-4 space-y-3">
    <p class="text-xs text-stone-600">Ou approuvez avec la clé de sécurité du superviseur :</p>
    <div class="flex flex-wrap items-center gap-2">
        <input type="text" id="{{ $widgetId }}-number" placeholder="Identifiant superviseur"
               class="border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none flex-1 min-w-[160px]">
        <button type="button" id="{{ $widgetId }}-trigger"
                class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            Utiliser la clé de sécurité
        </button>
    </div>
    <p id="{{ $widgetId }}-status" class="text-xs text-stone-500"></p>
    <input type="hidden" name="supervisor_security_key_credential" id="{{ $widgetId }}-credential">
</div>

<script>
(function () {
    const numberInput = document.getElementById('{{ $widgetId }}-number');
    const triggerBtn = document.getElementById('{{ $widgetId }}-trigger');
    const status = document.getElementById('{{ $widgetId }}-status');
    const credentialInput = document.getElementById('{{ $widgetId }}-credential');
    const optionsUrl = @json($optionsUrl);

    function setStatus(message, isError) {
        status.textContent = message;
        status.className = isError ? 'text-xs text-red-600' : 'text-xs text-green-700';
    }

    triggerBtn.addEventListener('click', async () => {
        const number = numberInput.value.trim();
        if (!number) {
            setStatus('Saisissez d’abord l’identifiant du superviseur.', true);
            return;
        }

        setStatus('Interrogez votre clé de sécurité...', false);

        try {
            const response = await fetch(optionsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
                },
                body: JSON.stringify({ supervisor_number: number }),
            });

            if (!response.ok) {
                setStatus('Impossible de générer le défi de sécurité.', true);
                return;
            }

            const optionsJson = await response.json();
            const credentialJson = await window.WebAuthnHelper.authenticate(optionsJson);

            credentialInput.value = JSON.stringify(credentialJson);
            setStatus('Clé de sécurité validée.', false);
        } catch (error) {
            setStatus('Clé de sécurité non reconnue ou opération annulée.', true);
        }
    });
})();
</script>
