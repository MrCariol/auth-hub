<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Utente') }}: {{ $user->name }}
        </h2>
    </x-slot>

    @php
        $statusLabels = [
            'active' => 'Attivo',
            'pending' => 'In attesa',
            'blocked' => 'Bloccato',
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-md p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-xs text-gray-500 mb-4">{{ __('Registrato il') }} {{ $user->created_at->format('d/m/Y H:i') }}</p>

                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label :value="__('Nome')" />
                            <x-text-input type="text" name="name" class="mt-1 block w-full" value="{{ old('name', $user->name) }}" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label :value="__('Email')" />
                            <x-text-input type="email" name="email" class="mt-1 block w-full" value="{{ old('email', $user->email) }}" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label :value="__('Stato')" />
                            <select name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $user->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-1" />
                        </div>

                        <div>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_admin" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('is_admin', $user->is_admin))>
                                <span class="ms-2 text-sm text-gray-600">{{ __('Amministratore') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('is_admin')" class="mt-1" />
                        </div>

                        <div class="flex items-center gap-3">
                            <x-primary-button type="submit">{{ __('Salva') }}</x-primary-button>
                            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Torna alla lista') }}</a>
                        </div>
                    </form>

                    @if ($user->status === 'pending')
                        <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition">
                                {{ __('Approva utente') }}
                            </button>
                        </form>
                    @endif

                    @unless ($user->is(auth()->user()))
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-6 pt-6 border-t border-gray-200" onsubmit="return confirm('{{ __('Eliminare :name? Non potrà più accedere.', ['name' => $user->name]) }}');">
                            @csrf
                            @method('DELETE')
                            <x-danger-button type="submit">{{ __('Elimina utente') }}</x-danger-button>
                        </form>
                    @endunless
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
