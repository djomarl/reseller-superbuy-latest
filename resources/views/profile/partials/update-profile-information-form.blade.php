<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="p-6 bg-slate-50 border border-slate-200 rounded-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <svg class="w-24 h-24 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>

            <h3 class="text-lg font-bold text-slate-800 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Superbuy Koppeling
            </h3>

            <div class="mb-4 relative z-10">
                @if($user->superbuy_cookie)
                    <div class="mb-4 bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800 text-sm">Verbinding Actief</h4>
                                <p class="text-xs text-slate-500">Je sessiegegevens zijn opgeslagen</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-start gap-2 text-xs">
                                <span class="font-mono font-bold text-slate-400 min-w-[80px]">User-Agent:</span>
                                <span class="font-mono text-slate-600 break-all bg-slate-50 p-1 rounded border border-slate-100">
                                    {{ $user->superbuy_user_agent ? Str::limit($user->superbuy_user_agent, 100) : 'Standaard' }}
                                </span>
                            </div>
                            <div class="flex items-start gap-2 text-xs">
                                <span class="font-mono font-bold text-slate-400 min-w-[80px]">Cookie:</span>
                                <span class="font-mono text-slate-600 break-all bg-slate-50 p-1 rounded border border-slate-100">
                                    {{ Str::limit($user->superbuy_cookie, 50) }}... ({{ strlen($user->superbuy_cookie) }} karakters)
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="@if($user->superbuy_cookie) opacity-50 hover:opacity-100 transition-opacity @endif">
                    <x-input-label for="superbuy_cookie" :value="__('Gegevens updaten')" class="text-base font-semibold text-slate-700" />
                    <p class="text-xs text-slate-500 mb-2">Plak hier een nieuwe cURL om je sessie te verversen.</p>
                    <textarea
                        id="superbuy_cookie"
                        name="superbuy_cookie"
                        rows="3"
                        class="mt-2 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg shadow-sm font-mono text-xs bg-white text-slate-600 leading-relaxed"
                        placeholder="Plak hier je cURL commando..."
                    >{{ old('superbuy_cookie', $user->superbuy_cookie) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('superbuy_cookie')" />
                </div>
            </div>

            <div x-data="{ showHelp: false }" class="relative z-10">
                <button type="button" @click="showHelp = !showHelp" class="text-sm text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Hulp nodig? Klik hier voor instructies</span>
                </button>

                <div x-show="showHelp" style="display: none;" class="mt-4 text-sm text-slate-600 bg-white p-5 rounded-lg border border-slate-200 shadow-sm">
                    <h4 class="font-bold text-slate-800 mb-2">Hoe vind ik mijn gegevens? (Aanbevolen methode)</h4>
                    <ol class="list-decimal ml-4 space-y-2 marker:text-indigo-500 marker:font-bold">
                        <li>Log in op <a href="https://www.superbuy.com" target="_blank" class="text-indigo-600 underline hover:text-indigo-800">Superbuy.com</a>.</li>
                        <li>Druk op <kbd class="bg-slate-100 border border-slate-300 rounded px-1.5 py-0.5 text-xs font-mono text-slate-500">F12</kbd> (of Rechtermuisknop -> Inspecteren).</li>
                        <li>Ga naar tabblad <strong>Network</strong> en ververs de pagina (<kbd>F5</kbd>).</li>
                        <li>Klik met de <strong>rechtermuisknop</strong> op het eerste request (vaak 'order' of 'www.superbuy.com').</li>
                        <li>Kies: <strong>Copy</strong> -> <strong>Copy as cURL (bash)</strong> (of 'Copy as cURL').</li>
                        <li>Plak het complete gekopieerde stuk tekst hierboven in het veld.</li>
                    </ol>
                    <div class="mt-4 p-3 bg-indigo-50 text-indigo-800 rounded border border-indigo-100 text-xs">
                        <p><strong>Tip:</strong> Door de cURL te kopiëren, pakken we in één keer de juiste Cookie én de User-Agent instellingen voor een betere connectie.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
