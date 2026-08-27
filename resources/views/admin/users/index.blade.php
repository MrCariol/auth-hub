<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Utenti') }}
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
                    <h3 class="text-lg font-medium mb-4">{{ __('Utenti registrati') }}</h3>

                    @if ($users->isEmpty())
                        <p class="text-sm text-gray-600">{{ __('Nessun utente registrato.') }}</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($users as $user)
                                @php
                                    $statusStyles = [
                                        'active' => 'bg-green-50 border-green-200 text-green-800',
                                        'pending' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                                        'blocked' => 'bg-red-50 border-red-200 text-red-800',
                                    ];
                                    $statusLabels = [
                                        'active' => 'Attivo',
                                        'pending' => 'In attesa',
                                        'blocked' => 'Bloccato',
                                    ];
                                @endphp
                                <div class="border rounded-md p-4">
                                    <div class="flex flex-wrap items-center gap-2 mb-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $statusStyles[$user->status] ?? 'bg-gray-50 border-gray-200 text-gray-800' }}">
                                            {{ $statusLabels[$user->status] ?? $user->status }}
                                        </span>

                                        @if ($user->is_admin)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border bg-indigo-50 border-indigo-200 text-indigo-800">
                                                {{ __('Admin') }}
                                            </span>
                                        @endif

                                        <span class="text-xs text-gray-500">{{ __('Registrato il') }} {{ $user->created_at->format('d/m/Y H:i') }}</span>
                                    </div>

                                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                                        @csrf
                                        @method('PUT')

                                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                            <div>
                                                <x-input-label :value="__('Nome')" />
                                                <x-text-input type="text" name="name" class="mt-1 block w-full" value="{{ old('name', $user->name) }}" required />
                                            </div>
                                            <div>
                                                <x-input-label :value="__('Email')" />
                                                <x-text-input type="email" name="email" class="mt-1 block w-full" value="{{ old('email', $user->email) }}" required />
                                            </div>
                                            <div>
                                                <x-input-label :value="__('Stato')" />
                                                <select name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                    @foreach ($statusLabels as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('status', $user->status) === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="flex items-end pb-2">
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" name="is_admin" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_admin', $user->is_admin))>
                                                    <span class="ms-2 text-sm text-gray-600">{{ __('Amministratore') }}</span>
                                                </label>
                                            </div>
                                        </div>

                                        <x-input-error :messages="$errors->get('is_admin')" class="mt-2" />
                                        <x-input-error :messages="$errors->get('status')" class="mt-2" />

                                        <div class="mt-3">
                                            <x-primary-button type="submit">{{ __('Salva') }}</x-primary-button>
                                        </div>
                                    </form>

                                    @if ($user->status === 'pending')
                                        <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="mt-2">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition">
                                                {{ __('Approva') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
