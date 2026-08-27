<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('App') }}: {{ $pwaClient->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.pwa-clients.update', $pwaClient) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label :value="__('Name (used as ?client=...)')" />
                            <x-text-input type="text" name="name" class="mt-1 block w-full" value="{{ old('name', $pwaClient->name) }}" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label :value="__('Domain')" />
                            <x-text-input type="text" name="domain" class="mt-1 block w-full" value="{{ old('domain', $pwaClient->domain) }}" required />
                            <x-input-error :messages="$errors->get('domain')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label :value="__('Redirect path')" />
                            <x-text-input type="text" name="redirect_path" class="mt-1 block w-full" value="{{ old('redirect_path', $pwaClient->redirect_path) }}" required />
                            <x-input-error :messages="$errors->get('redirect_path')" class="mt-1" />
                        </div>

                        <p class="text-xs text-gray-500">{{ __('Callback URL') }}: https://{{ $pwaClient->domain }}{{ $pwaClient->redirect_path }}</p>

                        <div class="flex items-center gap-3">
                            <x-primary-button type="submit">{{ __('Salva') }}</x-primary-button>
                            <a href="{{ route('admin.pwa-clients.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Torna alla lista') }}</a>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.pwa-clients.destroy', $pwaClient) }}" class="mt-6 pt-6 border-t border-gray-200" onsubmit="return confirm('{{ __('Eliminare :name?', ['name' => $pwaClient->name]) }}');">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">{{ __('Elimina app') }}</x-danger-button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
