<x-employee-layout title="Détails du superviseur" subtitle="{{ $supervisor->supervisor_number }}">
    <div class="mb-4">
        <a href="{{ route('employee.supervisors.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </div>

    <div class="max-w-2xl bg-white border border-stone-200 rounded-xl p-5 shadow-sm space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-stone-800">Informations</h2>
            <p class="text-sm text-stone-500 mt-1">Utilisez ce QR code pour un bypass superviseur depuis l'application mobile.</p>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-stone-500">Identifiant superviseur</dt>
                <dd class="font-mono text-stone-800 mt-1">{{ $supervisor->supervisor_number }}</dd>
            </div>
            <div>
                <dt class="text-stone-500">Statut</dt>
                <dd class="mt-1">
                    @if($supervisor->is_active)
                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-700">Actif</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full bg-stone-100 text-stone-600">Désactivé</span>
                    @endif
                </dd>
            </div>
        </dl>

        <div class="border border-stone-200 rounded-lg p-4 bg-stone-50">
            <p class="text-sm text-stone-600 mb-3">QR code de bypass superviseur</p>
            <div class="bg-white border border-stone-200 rounded-lg p-3 overflow-x-auto flex justify-center">
                <canvas id="supervisor-qr"></canvas>
            </div>
            <p class="text-xs text-stone-500 mt-3">Format court signé pour un affichage compact et un scan mobile plus fiable.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
    <script>
        const value = @json($barcodeValue);
        const canvas = document.getElementById('supervisor-qr');
        QRCode.toCanvas(canvas, value, {
            width: 220,
            margin: 1,
            color: {
                dark: '#1f2937',
                light: '#ffffff',
            },
        }, function (error) {
            if (error) {
                console.error('QR generation failed:', error);
            }
        });
    </script>
</x-employee-layout>
