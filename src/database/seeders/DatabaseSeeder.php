<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            DeputySeeder::class,
            UnitKerjaSeeder::class,
            SuperadminSeeder::class,
            CategorySeeder::class,
            StatusTemplateSeeder::class,
            DocumentTemplateSeeder::class,
            // TestingUsersSeeder::class,
            CategoryUnitSeeder::class,
            ProvinceMapIdSeeder::class,
            KmsArticleSeeder::class,
        ]);
    }
}
