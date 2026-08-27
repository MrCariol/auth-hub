<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('email');
            $table->boolean('is_admin')->default(false)->after('status');
        });

        // Gli utenti gia' esistenti hanno gia' accesso oggi: non farli restare
        // bloccati in "pending" solo perche' il campo status non esisteva ancora.
        DB::table('users')->update(['status' => 'active']);

        // L'admin storico era identificato solo via MAIL_ADMIN_NOTIFICATION_ADDRESS:
        // promuovilo a is_admin cosi' non perde l'accesso al pannello admin.
        // L'indirizzo di notifica puo' usare un alias "+tag" (es. Gmail) diverso
        // dall'email di login vera e propria, quindi il confronto normalizza
        // entrambi togliendo la parte "+..." prima della chiocciola.
        $adminEmail = config('mail.admin_notification_address');

        if ($adminEmail) {
            $normalizedAdminEmail = Str::lower(preg_replace('/\+[^@]*(?=@)/', '', $adminEmail));

            DB::table('users')->get(['id', 'email'])->each(function ($user) use ($normalizedAdminEmail) {
                $normalizedUserEmail = Str::lower(preg_replace('/\+[^@]*(?=@)/', '', $user->email));

                if ($normalizedUserEmail === $normalizedAdminEmail) {
                    DB::table('users')->where('id', $user->id)->update(['is_admin' => true]);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_admin']);
        });
    }
};
