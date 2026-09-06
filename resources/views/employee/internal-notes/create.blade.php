<x-employee-layout title="Nouvelle note interne">
    <x-slot name="headerActions">
        <a href="{{ route('employee.internal-notes.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">Retour à la messagerie</a>
    </x-slot>

    @php
        $selectedFontFamily = old('font_family', 'Figtree, ui-sans-serif, system-ui, sans-serif');
    @endphp

    <form action="{{ route('employee.internal-notes.store') }}" method="POST" enctype="multipart/form-data" class="max-w-5xl grid lg:grid-cols-[minmax(0,1fr)_20rem] gap-5 items-start" x-data="noteEditor()" @submit="syncDescription()">
        @csrf

        <div class="bg-white border border-stone-200 rounded-lg p-6 space-y-5">
            <div>
                <label for="title" class="block text-sm font-medium text-stone-700 mb-1">Titre</label>
                <input id="title" name="title" x-model="title" value="{{ old('title') }}" maxlength="150" required class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-amber-500">
                @error('title')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-stone-700 mb-1">Description</label>
                <div class="border border-stone-300 rounded-lg overflow-hidden">
                    <div class="flex flex-wrap gap-1 p-2 bg-stone-50 border-b border-stone-200">
                        <button type="button" @click="format('bold')" class="w-9 h-8 font-bold text-stone-700 hover:bg-stone-200 rounded" title="Gras">B</button>
                        <button type="button" @click="format('italic')" class="w-9 h-8 italic text-stone-700 hover:bg-stone-200 rounded" title="Italique">I</button>
                        <button type="button" @click="format('underline')" class="w-9 h-8 underline text-stone-700 hover:bg-stone-200 rounded" title="Souligné">U</button>
                        <button type="button" @click="format('insertUnorderedList')" class="px-3 h-8 text-stone-700 hover:bg-stone-200 rounded text-sm" title="Liste">Liste</button>
                        <button type="button" @click="format('insertOrderedList')" class="px-3 h-8 text-stone-700 hover:bg-stone-200 rounded text-sm" title="Liste numérotée">1.</button>
                        <button type="button" @click="format('removeFormat')" class="px-3 h-8 text-stone-700 hover:bg-stone-200 rounded text-sm" title="Supprimer le format">Effacer</button>
                    </div>
                    <div x-ref="editor" contenteditable="true" @input="syncDescription()" class="min-h-52 p-4 text-sm text-stone-700 outline-none" aria-label="Description de la note">{{ old('description') }}</div>
                </div>
                <input type="hidden" name="description" x-model="description">
                @error('description')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="display_location" class="block text-sm font-medium text-stone-700 mb-1">Emplacement</label>
                <select id="display_location" name="display_location" x-model="location" class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="inbox">Messagerie interne</option>
                    <option value="employee_banner">Bannière de l'espace salarié</option>
                    <option value="global_banner">Bannière globale du site public</option>
                </select>
            </div>

            <div x-show="location !== 'global_banner'" x-cloak class="grid md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="flex items-center gap-2 text-sm font-medium text-stone-700 cursor-pointer">
                        <input type="radio" name="audience" value="all" x-model="audience" class="text-amber-600 focus:ring-amber-500">
                        Tous les salariés
                    </label>
                    <label class="flex items-center gap-2 text-sm font-medium text-stone-700 cursor-pointer mt-2">
                        <input type="radio" name="audience" value="targeted" x-model="audience" class="text-amber-600 focus:ring-amber-500">
                        Destinataires ciblés
                    </label>
                </div>
                <div x-show="audience === 'targeted'">
                    <label for="target_role" class="block text-sm font-medium text-stone-700 mb-1">Type de destinataire</label>
                    <select id="target_role" name="target_role" class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm">
                        <option value="">Aucun type sélectionné</option>
                        <option value="superadmin" @selected(old('target_role') === 'superadmin')>Super administrateurs</option>
                        <option value="admin" @selected(old('target_role') === 'admin')>Administrateurs</option>
                        <option value="moderator" @selected(old('target_role') === 'moderator')>Modérateurs</option>
                    </select>
                </div>
                <div x-show="audience === 'targeted'">
                    <label for="recipient_ids" class="block text-sm font-medium text-stone-700 mb-1">Utilisateurs précis</label>
                    <select id="recipient_ids" name="recipient_ids[]" multiple class="w-full border border-stone-300 rounded-lg px-4 py-2 text-sm min-h-28">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(in_array($user->id, old('recipient_ids', [])))>{{ $user->name }} ({{ $user->username }})</option>
                        @endforeach
                    </select>
                </div>
                @error('recipient_ids')<p class="md:col-span-2 text-red-600 text-xs">{{ $message }}</p>@enderror
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label for="background_color" class="block text-sm font-medium text-stone-700 mb-1">Couleur de fond</label>
                    <input id="background_color" name="background_color" x-model="backgroundColor" type="color" value="{{ old('background_color', '#FEF3C7') }}" class="w-full h-10 border border-stone-300 rounded-lg">
                </div>
                <div>
                    <label for="text_color" class="block text-sm font-medium text-stone-700 mb-1">Couleur du texte</label>
                    <input id="text_color" name="text_color" x-model="textColor" type="color" value="{{ old('text_color', '#78350F') }}" class="w-full h-10 border border-stone-300 rounded-lg">
                </div>
                <div>
                    <label for="font_family" class="block text-sm font-medium text-stone-700 mb-1">Police</label>
                    <select id="font_family" name="font_family" x-model="fontFamily" class="w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm">
                        @foreach(\App\Models\InternalNote::FONT_FAMILIES as $value => $label)
                            <option value="{{ $value }}" @selected($selectedFontFamily === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="expires_at" class="block text-sm font-medium text-stone-700 mb-1">Supprimer automatiquement après</label>
                <input id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at') }}" class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm">
                <p class="text-xs text-stone-500 mt-1">Laissez vide pour conserver la note. À la date indiquée, elle est masquée puis supprimée lors de la purge quotidienne.</p>
                @error('expires_at')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="attachments" class="block text-sm font-medium text-stone-700 mb-1">Pièces jointes</label>
                <input id="attachments" name="attachments[]" type="file" multiple class="w-full text-sm text-stone-600">
                <p class="text-xs text-stone-500 mt-1">3 fichiers maximum, 5 Mo maximum par fichier.</p>
                @error('attachments')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <aside class="lg:sticky lg:top-6 bg-white border border-stone-200 rounded-lg p-5 space-y-3">
            <div>
                <h2 class="font-semibold text-stone-800">Aperçu</h2>
                <p class="text-xs text-stone-500 mt-1">Le rendu est mis à jour pendant la rédaction.</p>
            </div>
            <section class="rounded-lg border p-4 shadow-sm" :style="`background-color: ${backgroundColor}; color: ${textColor}; font-family: ${fontFamily}; border-color: ${textColor}33;`">
                <p class="font-semibold text-sm" x-text="title || 'Titre de la note'"></p>
                <div class="mt-2 text-sm leading-relaxed rich-note-content" x-html="description || 'Le contenu de la note apparaîtra ici.'"></div>
            </section>
        </aside>

        <div class="lg:col-span-2 flex gap-3">
            <button type="submit" class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium">Envoyer la note</button>
            <a href="{{ route('employee.internal-notes.index') }}" class="bg-stone-200 hover:bg-stone-300 text-stone-700 px-5 py-2.5 rounded-lg text-sm font-medium">Annuler</a>
        </div>
    </form>

    <script>
        function noteEditor() {
            return {
                location: @json(old('display_location', 'inbox')),
                audience: @json(old('audience', 'targeted')),
                title: @json(old('title', '')),
                description: @json(old('description', '')),
                backgroundColor: @json(old('background_color', '#FEF3C7')),
                textColor: @json(old('text_color', '#78350F')),
                fontFamily: @json($selectedFontFamily),
                format(command) {
                    document.execCommand(command, false, null);
                    this.syncDescription();
                },
                syncDescription() {
                    this.description = this.$refs.editor.innerHTML;
                },
            };
        }
    </script>
</x-employee-layout>
