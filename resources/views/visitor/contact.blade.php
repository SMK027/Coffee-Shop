<x-visitor-layout title="Contact" description="Contactez Le Coffee Shop pour des partenariats, questions ou suggestions">

    {{-- Hero --}}
    <div class="bg-amber-900 text-white py-16 text-center relative">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative max-w-3xl mx-auto px-4">
            <p class="text-amber-300 text-sm font-semibold uppercase tracking-widest mb-3">Échangeons</p>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Nous Contacter</h1>
            <p class="text-amber-100 text-lg">Une question, une idée de partenariat ou simplement envie de nous dire bonjour ? Nous sommes à votre écoute.</p>
        </div>
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 40L1440 40L1440 20C1200 40 960 0 720 20C480 40 240 0 0 20L0 40Z" fill="#fafaf9"/>
            </svg>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid md:grid-cols-3 gap-12">

            {{-- Informations --}}
            <div class="md:col-span-1">
                <h2 class="text-xl font-bold text-stone-800 mb-6">Informations</h2>
                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-stone-800 text-sm">Adresse</p>
                            <p class="text-stone-600 text-sm mt-1">12 Rue des Arômes<br>75001 Paris</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-stone-800 text-sm">Horaires</p>
                            <div class="text-stone-600 text-sm mt-1 space-y-0.5">
                                <p>Lun – Ven : 7h00 – 19h00</p>
                                <p>Samedi : 8h00 – 20h00</p>
                                <p>Dimanche : 9h00 – 18h00</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-stone-800 text-sm">Email</p>
                            <p class="text-stone-600 text-sm mt-1">contact@lecoffeeshop.fr</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 p-4 bg-amber-50 rounded-xl border border-amber-100">
                    <p class="text-sm font-medium text-amber-800 mb-1">Partenariats</p>
                    <p class="text-sm text-amber-700">Pour toute proposition de partenariat commercial, mentionnez-le dans l'objet de votre message.</p>
                </div>
            </div>

            {{-- Formulaire --}}
            <div class="md:col-span-2">
                <h2 class="text-xl font-bold text-stone-800 mb-6">Envoyer un message</h2>
                <form action="{{ route('contact.submit') }}" method="POST" class="space-y-5">
                    @csrf
                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-stone-700 mb-1.5">Nom complet *</label>
                            <input type="text" name="name" id="name" required maxlength="100"
                                   value="{{ old('name') }}"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors">
                            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-stone-700 mb-1.5">Adresse email *</label>
                            <input type="email" name="email" id="email" required maxlength="150"
                                   value="{{ old('email') }}"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors">
                            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label for="subject" class="block text-sm font-medium text-stone-700 mb-1.5">Objet *</label>
                        <input type="text" name="subject" id="subject" required maxlength="200"
                               value="{{ old('subject') }}"
                               placeholder="Ex: Proposition de partenariat, Question sur le menu..."
                               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-colors">
                        @error('subject')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-stone-700 mb-1.5">Message *</label>
                        <textarea name="message" id="message" required minlength="20" maxlength="2000" rows="6"
                                  class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none transition-colors">{{ old('message') }}</textarea>
                        @error('message')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <button type="submit"
                                class="w-full sm:w-auto bg-amber-700 hover:bg-amber-600 text-white px-8 py-3 rounded-lg font-semibold transition-colors text-sm shadow-sm">
                            Envoyer le message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-visitor-layout>
