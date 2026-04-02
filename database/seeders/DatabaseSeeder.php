<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name'     => 'Administrador',
            'email'    => 'admin@sos.com',
            'password' => bcrypt('admin123'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Consulta',
            'email'    => 'consulta@sos.com',
            'password' => bcrypt('consulta123'),
            'role'     => 'consulta',
        ]);
    }
}
