<?php

namespace Database\Seeders;

use App\Models\PwaClient;
use Illuminate\Database\Seeder;

class PwaClientSeeder extends Seeder
{
    public function run(): void
    {
        PwaClient::updateOrCreate(
            ['name' => 'routine'],
            ['domain' => 'routine.example.it', 'redirect_path' => '/auth/callback']
        );
    }
}
