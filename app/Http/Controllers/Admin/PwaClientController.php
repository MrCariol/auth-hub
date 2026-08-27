<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PwaClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PwaClientController extends Controller
{
    public function index(): View
    {
        return view('admin.pwa-clients.index', [
            'pwaClients' => PwaClient::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        PwaClient::create($data);

        return redirect()->route('admin.pwa-clients.index')->with('status', 'App creata.');
    }

    public function edit(PwaClient $pwaClient): View
    {
        return view('admin.pwa-clients.edit', ['pwaClient' => $pwaClient]);
    }

    public function update(Request $request, PwaClient $pwaClient): RedirectResponse
    {
        $data = $this->validated($request, $pwaClient);

        $pwaClient->update($data);

        return redirect()->route('admin.pwa-clients.index')->with('status', 'App aggiornata.');
    }

    public function destroy(PwaClient $pwaClient): RedirectResponse
    {
        $pwaClient->delete();

        return redirect()->route('admin.pwa-clients.index')->with('status', 'App eliminata.');
    }

    /**
     * @return array{name: string, domain: string, redirect_path: string}
     */
    private function validated(Request $request, ?PwaClient $ignoring = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                'unique:pwa_clients,name' . ($ignoring ? ',' . $ignoring->id : ''),
            ],
            'domain' => ['required', 'string', 'max:255'],
            'redirect_path' => ['required', 'string', 'max:255', 'starts_with:/'],
        ], [
            'name.regex' => 'Solo lettere minuscole, numeri e trattini (è il valore usato in ?client=...).',
            'redirect_path.starts_with' => 'Deve iniziare con /.',
        ]);
    }
}
