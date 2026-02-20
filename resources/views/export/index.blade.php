<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Export / Import') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-start gap-3">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-start gap-3">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v5a1 1 0 002 0V5zm-1 10a1.25 1.25 0 100-2.5 1.25 1.25 0 000 2.5z" clip-rule="evenodd"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Export Sectie --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Data Exporteren') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Download al je data als een JSON bestand. Dit bevat je items, parcels, templates en instellingen.') }}
                            </p>
                        </header>

                        <div class="mt-6 flex items-center gap-4">
                            <a href="{{ route('export.download') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V3"/></svg>
                                {{ __('Download Export') }}
                            </a>
                            <p class="text-sm text-gray-500">
                                {{ __('Het bestand wordt direct gedownload.') }}
                            </p>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Import Sectie --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Data Importeren') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('Upload een eerder geëxporteerd JSON bestand om je data te synchroniseren. Bestaande items worden overgeslagen, alleen nieuwe items worden toegevoegd.') }}
                            </p>
                        </header>

                        <form method="POST" action="{{ route('export.import') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                            @csrf

                            <div>
                                <x-input-label for="file" :value="__('JSON Bestand')" />
                                <input
                                    type="file"
                                    id="file"
                                    name="file"
                                    accept=".json"
                                    required
                                    class="mt-1 block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-gray-800 file:text-white
                                        hover:file:bg-gray-700
                                        file:cursor-pointer file:transition file:ease-in-out file:duration-150"
                                />
                                @error('file')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Preview area --}}
                            <div id="preview" class="hidden p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <h3 class="text-sm font-medium text-gray-700 mb-2">{{ __('Preview') }}</h3>
                                <div id="preview-content" class="text-sm text-gray-600 space-y-1"></div>
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button id="import-btn">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 6l-4-4m0 0L8 6m4-4v13"/></svg>
                                    {{ __('Importeer Data') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>

            {{-- Info --}}
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('Hoe werkt het?') }}
                            </h2>
                        </header>
                        <div class="mt-4 text-sm text-gray-600 space-y-3">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-800 text-white text-xs font-bold rounded-full flex-shrink-0">1</span>
                                <p><strong>Exporteer</strong> je data op computer A via de knop hierboven.</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-800 text-white text-xs font-bold rounded-full flex-shrink-0">2</span>
                                <p><strong>Kopieer</strong> het JSON bestand naar computer B (via USB, e-mail, cloud, etc.).</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-gray-800 text-white text-xs font-bold rounded-full flex-shrink-0">3</span>
                                <p><strong>Importeer</strong> het bestand op computer B. Nieuwe items worden toegevoegd, bestaande items worden overgeslagen.</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

        </div>
    </div>

    {{-- Preview Script --}}
    <script>
        document.getElementById('file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('preview');
            const content = document.getElementById('preview-content');

            if (!file) {
                preview.classList.add('hidden');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = JSON.parse(e.target.result);
                    if (!data.meta) {
                        content.innerHTML = '<p class="text-red-600">⚠️ Ongeldig bestand — geen Reseller Pro export.</p>';
                        preview.classList.remove('hidden');
                        return;
                    }

                    let html = '';
                    html += `<p>📅 <strong>Export datum:</strong> ${new Date(data.meta.exported_at).toLocaleString('nl-NL')}</p>`;
                    html += `<p>👤 <strong>User:</strong> ${data.meta.user || '—'}</p>`;
                    html += `<p>📦 <strong>Items:</strong> ${(data.items || []).length}</p>`;
                    html += `<p>🚚 <strong>Parcels:</strong> ${(data.parcels || []).length}</p>`;
                    html += `<p>📋 <strong>Templates:</strong> ${(data.item_templates || []).length}</p>`;
                    html += `<p>⚙️ <strong>Settings:</strong> ${data.user_settings ? 'Ja' : 'Nee'}</p>`;

                    content.innerHTML = html;
                    preview.classList.remove('hidden');
                } catch (err) {
                    content.innerHTML = '<p class="text-red-600">⚠️ Kan bestand niet lezen — ongeldig JSON.</p>';
                    preview.classList.remove('hidden');
                }
            };
            reader.readAsText(file);
        });
    </script>
</x-app-layout>
