<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Extensie Koppeling') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Gebruik deze sleutel om de Chrome Extensie te koppelen aan jouw account.') }}
                            </p>
                        </header>

                        <div class="mt-6 space-y-6">
                            <form method="post" action="{{ route('profile.secret') }}" class="mt-6 space-y-6">
                                @csrf
                                @method('patch')

                                <div>
                                    <x-input-label for="sync_secret" :value="__('Jouw Sync Secret')" />
                                    
                                    <div class="flex gap-2 mt-1">
                                        @if($user->sync_secret)
                                            <x-text-input 
                                                id="sync_secret" 
                                                type="text" 
                                                class="block w-full bg-gray-100 cursor-not-allowed" 
                                                :value="$user->sync_secret" 
                                                readonly 
                                                onclick="this.select()"
                                            />
                                            <x-secondary-button type="button" onclick="navigator.clipboard.writeText(document.getElementById('sync_secret').value); alert('Gekopieerd!');">
                                                {{ __('Kopieer') }}
                                            </x-secondary-button>
                                        @else
                                            <div class="block w-full p-2 text-gray-500 italic border border-gray-300 rounded bg-gray-50">
                                                Nog geen secret gegenereerd.
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    @if(!$user->sync_secret)
                                        <x-primary-button>{{ __('Genereer Secret') }}</x-primary-button>
                                    @else
                                        <x-danger-button onclick="return confirm('Weet je het zeker? Je moet de extensie hierna opnieuw instellen!')">
                                            {{ __('Genereer Nieuwe') }}
                                        </x-danger-button>
                                    @endif

                                    @if (session('status') === 'secret-updated')
                                        <p
                                            x-data="{ show: true }"
                                            x-show="show"
                                            x-transition
                                            x-init="setTimeout(() => show = false, 2000)"
                                            class="text-sm text-gray-600"
                                        >{{ __('Gegenereerd!') }}</p>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </section>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
