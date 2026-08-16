<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('PWA Clients') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">{{ __('Registered clients') }}</h3>

                    @if ($pwaClients->isEmpty())
                        <p class="text-sm text-gray-600">{{ __('No PWA client registered yet.') }}</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($pwaClients as $client)
                                <form method="POST" action="{{ route('admin.pwa-clients.update', $client) }}" class="border rounded-md p-4">
                                    @csrf
                                    @method('PUT')

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <x-input-label :value="__('Name (used as ?client=...)')" />
                                            <x-text-input type="text" name="name" class="mt-1 block w-full" value="{{ old('name', $client->name) }}" required />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Domain')" />
                                            <x-text-input type="text" name="domain" class="mt-1 block w-full" value="{{ old('domain', $client->domain) }}" required />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Redirect path')" />
                                            <x-text-input type="text" name="redirect_path" class="mt-1 block w-full" value="{{ old('redirect_path', $client->redirect_path) }}" required />
                                        </div>
                                    </div>

                                    <p class="text-xs text-gray-500 mt-2">{{ __('Callback URL') }}: https://{{ $client->domain }}{{ $client->redirect_path }}</p>

                                    <div class="flex items-center justify-between mt-3">
                                        <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                                    </div>
                                </form>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            @foreach ($pwaClients as $client)
                                <form method="POST" action="{{ route('admin.pwa-clients.destroy', $client) }}" class="inline" onsubmit="return confirm('{{ __('Delete client :name?', ['name' => $client->name]) }}');">
                                    @csrf
                                    @method('DELETE')
                                    <x-danger-button type="submit" class="mr-2 mb-2">{{ __('Delete') }} {{ $client->name }}</x-danger-button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">{{ __('Add new client') }}</h3>

                    <form method="POST" action="{{ route('admin.pwa-clients.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <x-input-label for="new_name" :value="__('Name (used as ?client=...)')" />
                                <x-text-input id="new_name" type="text" name="name" class="mt-1 block w-full" value="{{ old('name') }}" placeholder="shopping-kart" />
                                <x-input-error :messages="$errors->get('name')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="new_domain" :value="__('Domain')" />
                                <x-text-input id="new_domain" type="text" name="domain" class="mt-1 block w-full" value="{{ old('domain') }}" placeholder="shoppingkart.example.it" />
                                <x-input-error :messages="$errors->get('domain')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="new_redirect_path" :value="__('Redirect path')" />
                                <x-text-input id="new_redirect_path" type="text" name="redirect_path" class="mt-1 block w-full" value="{{ old('redirect_path', '/') }}" placeholder="/" />
                                <x-input-error :messages="$errors->get('redirect_path')" class="mt-1" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <x-primary-button type="submit">{{ __('Add client') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
