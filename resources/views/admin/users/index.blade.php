<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Utenti') }}
        </h2>
    </x-slot>

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

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <th class="px-3 py-2">{{ __('Nome') }}</th>
                                        <th class="px-3 py-2">{{ __('Email') }}</th>
                                        <th class="px-3 py-2">{{ __('Stato') }}</th>
                                        <th class="px-3 py-2">{{ __('Ruolo') }}</th>
                                        <th class="px-3 py-2">{{ __('Registrato il') }}</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($users as $user)
                                        <tr>
                                            <td class="px-3 py-2 font-medium text-gray-900 whitespace-nowrap">{{ $user->name }}</td>
                                            <td class="px-3 py-2 text-gray-600">{{ $user->email }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border {{ $statusStyles[$user->status] ?? 'bg-gray-50 border-gray-200 text-gray-800' }}">
                                                    {{ $statusLabels[$user->status] ?? $user->status }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                @if ($user->is_admin)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border bg-indigo-50 border-indigo-200 text-indigo-800">
                                                        {{ __('Admin') }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-gray-500 whitespace-nowrap">{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                <div class="flex items-center justify-end gap-3">
                                                    @if ($user->status === 'pending')
                                                        <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                                            @csrf
                                                            <button type="submit" class="text-green-700 hover:text-green-900 font-medium">{{ __('Approva') }}</button>
                                                        </form>
                                                    @endif

                                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">{{ __('Modifica') }}</a>

                                                    @unless ($user->is(auth()->user()))
                                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('Eliminare :name? Non potrà più accedere.', ['name' => $user->name]) }}');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-900 font-medium">{{ __('Elimina') }}</button>
                                                        </form>
                                                    @endunless
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

        </div>
    </div>
</x-app-layout>
