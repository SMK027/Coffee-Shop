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
                <dt class="text-stone-500">Responsable</dt>
                <dd class="text-stone-800 mt-1">{{ $supervisor->superadmin?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-stone-500">Détenteur</dt>
                <dd class="text-stone-800 mt-1">{{ $supervisor->holderAdmin?->name ?? 'Non défini' }}</dd>
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
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=0&data={{ urlencode($barcodeValue) }}"
                    alt="QR code superviseur"
                    width="220"
                    height="220"
                    class="block"
                >
            </div>
            <p class="text-xs text-stone-500 mt-3">Format court signé pour un affichage compact et un scan mobile plus fiable.</p>
        </div>

        @if(! $isSuperAdmin)
            <div class="border border-red-200 rounded-lg p-4 bg-red-50 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-red-800">Suppression protégée</h3>
                    <p class="text-xs text-red-700 mt-1">
                        La suppression nécessite l'authentification d'un autre superviseur que celui-ci.
                    </p>
                </div>

                <form action="{{ route('employee.supervisors.destroy', $supervisor) }}" method="POST" class="space-y-4"
                      onsubmit="return confirm('Confirmer la suppression de ce superviseur ?')">
                    @csrf @method('DELETE')
                    @include('employee.shared.supervisor-auth-fields')
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Supprimer ce superviseur
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-employee-layout>
