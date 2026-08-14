<x-employee-layout title="Validation superviseur réussie" subtitle="Exécution de l'action en cours">
    <div class="max-w-2xl space-y-5">
        <div class="bg-green-50 border border-green-200 rounded-xl p-5 space-y-2">
            <p class="text-sm font-semibold text-green-900">Autorisation accordée.</p>
            <p class="text-sm text-green-800">L'action demandée va être exécutée automatiquement.</p>
            <p class="text-xs text-green-700">Si rien ne se passe, utilisez le bouton ci-dessous.</p>
        </div>

        <form id="supervision-replay-form" action="{{ $actionPath }}" method="POST" class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 space-y-4">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            @include('employee.supervision.partials.hidden-fields', ['data' => $payload])

            <button type="submit"
                    class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Exécuter maintenant
            </button>
        </form>
    </div>

    <script>
        window.addEventListener('load', function () {
            const form = document.getElementById('supervision-replay-form');
            if (form) {
                form.submit();
            }
        });
    </script>
</x-employee-layout>
