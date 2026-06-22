<x-employee-layout title="Images d'accueil">

    @if(session('success'))
        <div class="mb-5 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-2 max-w-4xl">

        @foreach($images as $key => $image)
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden flex flex-col">

            {{-- Aperçu --}}
            <div class="relative bg-stone-100 aspect-video flex items-center justify-center overflow-hidden">
                @if($image['path'])
                    <img src="{{ Storage::url($image['path']) }}"
                         alt="{{ $image['label'] }}"
                         loading="lazy"
                         class="w-full h-full object-cover">
                    {{-- Badge suppression --}}
                    <form action="{{ route('employee.home-images.destroy', $key) }}" method="POST"
                          class="absolute top-2 right-2"
                          onsubmit="return confirm('Supprimer cette image ?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="bg-red-600 hover:bg-red-700 text-white rounded-lg p-1.5 shadow transition-colors"
                                title="Supprimer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                @else
                    <div class="text-center text-stone-400 py-8">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm font-medium opacity-60">Aucune image</p>
                    </div>
                @endif
            </div>

            {{-- Infos + formulaire upload --}}
            <div class="p-4 flex flex-col gap-3 flex-1">
                <p class="font-semibold text-stone-800 text-sm">{{ $image['label'] }}</p>

                <form action="{{ route('employee.home-images.update', $key) }}" method="POST"
                      enctype="multipart/form-data" class="flex flex-col gap-2">
                    @csrf

                    <label class="group relative flex flex-col items-center justify-center gap-2 border-2 border-dashed border-stone-300 hover:border-amber-500 rounded-lg p-4 cursor-pointer transition-colors bg-stone-50 hover:bg-amber-50"
                           for="image-{{ $key }}">
                        <svg class="w-6 h-6 text-stone-400 group-hover:text-amber-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <span class="text-xs text-stone-500 group-hover:text-amber-700 text-center transition-colors">
                            Cliquez ou glissez une image<br>
                            <span class="text-stone-400">JPEG, PNG, WebP — max 4 Mo</span>
                        </span>
                        <input type="file" id="image-{{ $key }}" name="image"
                               accept="image/jpeg,image/png,image/webp"
                               class="sr-only"
                               onchange="previewImage(this, 'preview-{{ $key }}'); this.form.submit()">
                    </label>

                    {{-- Aperçu local avant envoi --}}
                    <img id="preview-{{ $key }}" src="#" alt="Aperçu" class="hidden w-full rounded-lg object-cover max-h-32">

                    @error("image")
                        <p class="text-red-500 text-xs">{{ $message }}</p>
                    @enderror
                </form>
            </div>
        </div>
        @endforeach

    </div>

    <p class="mt-6 text-xs text-stone-400">Les images sont immédiatement publiées sur la page d'accueil publique après envoi.</p>

    <script>
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview || !input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
    </script>

</x-employee-layout>
