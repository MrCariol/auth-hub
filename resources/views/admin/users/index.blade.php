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
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Nome') }}</th>
                                        <th>{{ __('Email') }}</th>
                                        <th>{{ __('Stato') }}</th>
                                        <th>{{ __('Ruolo') }}</th>
                                        <th>{{ __('Registrato il') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $statusBadges = [
                                            'active' => 'bg-success',
                                            'pending' => 'bg-warning text-dark',
                                            'blocked' => 'bg-danger',
                                        ];
                                    @endphp
                                    @foreach ($users as $user)
                                        <tr>
                                            <td class="fw-medium">{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="badge {{ $statusBadges[$user->status] ?? 'bg-secondary' }}">
                                                    {{ $statusLabels[$user->status] ?? $user->status }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($user->is_admin)
                                                    <span class="badge bg-primary">{{ __('Admin') }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    @if ($user->status === 'pending')
                                                        <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success">{{ __('Approva') }}</button>
                                                        </form>
                                                    @endif

                                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">{{ __('Modifica') }}</a>

                                                    @unless ($user->is(auth()->user()))
                                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('Eliminare :name? Non potrà più accedere.', ['name' => $user->name]) }}');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Elimina') }}</button>
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
