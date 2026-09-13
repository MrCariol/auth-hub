<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Apps') }}
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
                    <h3 class="text-lg font-medium mb-4">{{ __('App registrate') }}</h3>

                    @if ($pwaClients->isEmpty())
                        <p class="text-sm text-gray-600">{{ __('Nessuna app registrata.') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Nome') }}</th>
                                        <th>{{ __('Dominio') }}</th>
                                        <th>{{ __('Callback URL') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pwaClients as $client)
                                        <tr>
                                            <td class="fw-medium">{{ $client->name }}</td>
                                            <td>{{ $client->domain }}</td>
                                            <td class="text-muted">https://{{ $client->domain }}{{ $client->redirect_path }}</td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="{{ route('admin.pwa-clients.edit', $client) }}" class="btn btn-sm btn-outline-primary">{{ __('Modifica') }}</a>

                                                    <form method="POST" action="{{ route('admin.pwa-clients.destroy', $client) }}" onsubmit="return confirm('{{ __('Eliminare :name?', ['name' => $client->name]) }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Elimina') }}</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">{{ __('Aggiungi nuova app') }}</h3>

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
                                <x-text-input id="new_domain" type="text" name="domain" class="mt-1 block w-full" value="{{ old('domain') }}" placeholder="shopping-kart.example.com" />
                                <x-input-error :messages="$errors->get('domain')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="new_redirect_path" :value="__('Redirect path')" />
                                <x-text-input id="new_redirect_path" type="text" name="redirect_path" class="mt-1 block w-full" value="{{ old('redirect_path', '/') }}" placeholder="/" />
                                <x-input-error :messages="$errors->get('redirect_path')" class="mt-1" />
                            </div>
                        </div>

                        <div class="mt-4">
                            <x-primary-button type="submit">{{ __('Aggiungi app') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
