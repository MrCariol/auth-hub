<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::orderBy('created_at', 'desc')->get(),
        ]);
    }

    /**
     * Aggiorna nome/email/stato/admin. Status e is_admin sono volutamente
     * fuori dal Fillable del model (mass assignment solo per name/email/password
     * in fase di registrazione): qui vengono assegnati esplicitamente.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'status' => ['required', Rule::in(['pending', 'active', 'blocked'])],
        ]);

        $isAdmin = $request->boolean('is_admin');

        if ($user->is(Auth::user()) && ! $isAdmin) {
            return back()->withErrors(['is_admin' => 'Non puoi toglierti da solo i permessi di amministratore.']);
        }

        if ($user->is(Auth::user()) && $data['status'] !== 'active') {
            return back()->withErrors(['status' => 'Non puoi bloccare o sospendere il tuo stesso account.']);
        }

        $wasActive = $user->status === 'active';

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->status = $data['status'];
        $user->is_admin = $isAdmin;
        $user->save();

        // Se un utente attivo viene bloccato/sospeso, revoca subito i token
        // delle PWA gia' emessi: altrimenti resterebbero validi fino a scadenza.
        if ($wasActive && $data['status'] !== 'active') {
            $user->tokens()->delete();
        }

        return redirect()->route('admin.users.index')->with('status', 'Utente aggiornato.');
    }

    public function approve(User $user): RedirectResponse
    {
        $user->status = 'active';
        $user->save();

        return redirect()->route('admin.users.index')->with('status', "Utente {$user->name} approvato.");
    }
}
