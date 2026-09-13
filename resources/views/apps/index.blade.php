<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Le mie App') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($pwaClients->isEmpty())
                        <p class="text-sm text-gray-600">{{ __('Nessuna app disponibile al momento.') }}</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($pwaClients as $client)
                                <a href="{{ route('login', ['client' => $client->name]) }}"
                                   class="block border border-gray-200 rounded-lg p-4 hover:border-gray-400 hover:shadow-sm transition">
                                    <div class="font-medium text-gray-900">{{ $client->name }}</div>
                                    <div class="text-sm text-gray-500 mt-1">{{ $client->domain }}</div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
