<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion par QR code | {{ config('app.name', 'Le Coffee Shop') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-amber-950 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm" x-data="qrLoginPage()" x-init="init()">
        <div class="text-center mb-8">
            <svg class="w-12 h-12 text-amber-400 mx-auto mb-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.89 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"/>
                <path d="M18 3c0-1.1-.9-2-2-2H8C6.9 1 6 1.9 6 3v2h12V3z"/>
            </svg>
            <h1 class="text-2xl font-bold text-white">Connexion par QR code</h1>
            <p class="text-amber-300 text-sm mt-1">{{ config('app.name') }}</p>
        </div>

        <div class="bg-white rounded-2xl p-8 shadow-2xl space-y-5">
            @if(session('error'))
                <p class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ session('error') }}</p>
            @endif

            {{-- Étape 1 : scan du QR code du salarié --}}
            <div>
                <p class="text-sm font-semibold text-stone-800 mb-2">1. Scanner votre QR code personnel</p>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <button type="button" @click="startScanner('user')" x-show="!userScanning"
                            class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Scanner mon QR code
                    </button>
                    <button type="button" @click="stopScanner('user')" x-show="userScanning"
                            class="bg-stone-200 hover:bg-stone-300 text-stone-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Arrêter la caméra
                    </button>
                </div>
                <div id="user-reader" class="w-full rounded-lg overflow-hidden border border-stone-200" x-show="userScanning" x-cloak></div>
                <p class="text-xs mt-2" :class="userError ? 'text-red-600' : 'text-stone-500'" x-text="userStatus"></p>
            </div>

            {{-- Étape 2 : vérification en base (automatique après scan) --}}
            <template x-if="identifiedName">
                <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                    <p class="text-sm text-green-800">2. Compte vérifié : <strong x-text="identifiedName"></strong></p>
                </div>
            </template>

            {{-- Étape 3 : authentification superviseur obligatoire --}}
            <template x-if="identifiedName">
                <form :action="'{{ route('login.qr.store') }}'" method="POST" class="space-y-4 border-t border-stone-100 pt-4">
                    @csrf
                    <input type="hidden" name="token" :value="userToken">
                    <p class="text-sm font-semibold text-stone-800">3. Authentification superviseur obligatoire</p>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="startScanner('supervisor')" x-show="!supervisorScanning"
                                class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            Scanner le QR superviseur
                        </button>
                        <button type="button" @click="stopScanner('supervisor')" x-show="supervisorScanning"
                                class="bg-stone-200 hover:bg-stone-300 text-stone-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            Arrêter la caméra
                        </button>
                    </div>
                    <div id="supervisor-reader" class="w-full rounded-lg overflow-hidden border border-stone-200" x-show="supervisorScanning" x-cloak></div>
                    <input type="hidden" name="supervisor_token" :value="supervisorToken">
                    <p class="text-xs text-green-700" x-show="supervisorToken">QR superviseur détecté.</p>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-stone-700 mb-1">Identifiant superviseur</label>
                            <input type="text" name="supervisor_number" value="{{ old('supervisor_number') }}"
                                   class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-700 mb-1">PIN superviseur</label>
                            <input type="password" name="supervisor_pin" maxlength="6" minlength="4" inputmode="numeric" pattern="\d{4,6}"
                                   class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        </div>
                    </div>
                    @error('supervisor_pin')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
                    @error('token')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror

                    <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white py-3 rounded-lg font-semibold text-sm transition-colors">
                        Se connecter
                    </button>
                </form>
            </template>
        </div>

        <div class="text-center mt-6 space-y-2">
            <a href="{{ route('login') }}" class="block text-amber-300 hover:text-amber-200 text-sm transition-colors">← Connexion par mot de passe</a>
            <a href="{{ route('home') }}" class="block text-amber-300 hover:text-amber-200 text-sm transition-colors">Retour au site</a>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        function qrLoginPage() {
            return {
                userToken: '',
                userScanning: false,
                userStatus: 'En attente du scan.',
                userError: false,
                identifiedName: null,
                supervisorToken: '',
                supervisorScanning: false,
                scanners: {},

                init() {
                    const oldToken = @json(old('token', ''));
                    if (oldToken) {
                        this.userToken = oldToken;
                        this.identify();
                    }
                },

                async startScanner(target) {
                    if (!window.Html5Qrcode || this[target + 'Scanning']) return;
                    this[target + 'Scanning'] = true;
                    const readerId = target + '-reader';
                    const scanner = new Html5Qrcode(readerId);
                    this.scanners[target] = scanner;

                    try {
                        await scanner.start(
                            { facingMode: 'environment' },
                            { fps: 10, qrbox: { width: 220, height: 220 } },
                            async (decodedText) => {
                                if (target === 'user') {
                                    this.userToken = decodedText;
                                    await this.stopScanner('user');
                                    await this.identify();
                                } else {
                                    this.supervisorToken = decodedText;
                                    await this.stopScanner('supervisor');
                                }
                            },
                            () => {}
                        );
                    } catch (error) {
                        this[target + 'Scanning'] = false;
                        if (target === 'user') {
                            this.userStatus = 'Impossible d’accéder à la caméra.';
                            this.userError = true;
                        }
                    }
                },

                async stopScanner(target) {
                    const scanner = this.scanners[target];
                    if (scanner) {
                        try { await scanner.stop(); await scanner.clear(); } catch (e) {}
                    }
                    this[target + 'Scanning'] = false;
                },

                async identify() {
                    this.userStatus = 'Vérification en base...';
                    this.userError = false;
                    this.identifiedName = null;

                    try {
                        const response = await fetch('{{ route('login.qr.identify') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({ token: this.userToken }),
                        });
                        const data = await response.json();

                        if (!response.ok) {
                            this.userStatus = data.message ?? 'QR code invalide.';
                            this.userError = true;
                            return;
                        }

                        this.identifiedName = data.name;
                        this.userStatus = 'QR code reconnu.';
                    } catch (e) {
                        this.userStatus = 'Erreur réseau lors de la vérification.';
                        this.userError = true;
                    }
                },
            };
        }
    </script>
</body>
</html>
