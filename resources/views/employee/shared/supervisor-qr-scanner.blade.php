@php
    $scannerId = (string) ($scannerId ?? 'supervisor-qr');
@endphp

<input type="hidden" name="supervisor_token" id="{{ $scannerId }}-token" value="{{ old('supervisor_token') }}">

<div class="border border-stone-200 rounded-xl p-4 space-y-3">
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" id="{{ $scannerId }}-start"
                class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            Scanner un QR code
        </button>
        <button type="button" id="{{ $scannerId }}-stop"
                class="bg-stone-200 hover:bg-stone-300 text-stone-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors hidden">
            Arrêter la caméra
        </button>
        <span id="{{ $scannerId }}-status" class="text-xs text-stone-500">Scannez le QR superviseur pour autoriser cette opération.</span>
    </div>

    <div id="{{ $scannerId }}-scanner" class="hidden">
        <div id="{{ $scannerId }}-reader" class="w-full max-w-sm rounded-lg overflow-hidden border border-stone-200"></div>
    </div>
</div>

@error('supervisor_token')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    (function () {
        const tokenInput = document.getElementById('{{ $scannerId }}-token');
        const startButton = document.getElementById('{{ $scannerId }}-start');
        const stopButton = document.getElementById('{{ $scannerId }}-stop');
        const status = document.getElementById('{{ $scannerId }}-status');
        const scannerContainer = document.getElementById('{{ $scannerId }}-scanner');
        const readerId = '{{ $scannerId }}-reader';

        let scanner = null;
        let scanning = false;

        function setStatus(message, isError) {
            status.textContent = message;
            status.className = isError ? 'text-xs text-red-600' : 'text-xs text-stone-500';
        }

        async function stopScanner() {
            if (!scanner || !scanning) {
                return;
            }

            try {
                await scanner.stop();
                await scanner.clear();
            } catch (error) {
                // The scanner may already be stopped after a successful read.
            }

            scanning = false;
            scannerContainer.classList.add('hidden');
            stopButton.classList.add('hidden');
            startButton.classList.remove('hidden');
        }

        async function startScanner() {
            if (!window.Html5Qrcode || scanning) {
                return;
            }

            scannerContainer.classList.remove('hidden');
            startButton.classList.add('hidden');
            stopButton.classList.remove('hidden');
            setStatus('Initialisation de la caméra...', false);
            scanner = new Html5Qrcode(readerId);

            try {
                await scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 220, height: 220 } },
                    async (decodedText) => {
                        tokenInput.value = decodedText;
                        setStatus('QR superviseur détecté.', false);
                        await stopScanner();
                    },
                    () => {}
                );

                scanning = true;
                setStatus('Caméra active. Cadrez le QR code superviseur.', false);
            } catch (error) {
                scannerContainer.classList.add('hidden');
                stopButton.classList.add('hidden');
                startButton.classList.remove('hidden');
                setStatus('Impossible d’accéder à la caméra. Vérifiez les permissions navigateur.', true);
            }
        }

        startButton.addEventListener('click', startScanner);
        stopButton.addEventListener('click', function () {
            stopScanner();
            setStatus('Scanner arrêté.', false);
        });
        window.addEventListener('beforeunload', stopScanner);
    })();
</script>
