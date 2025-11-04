<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProvinceMapId;
use Illuminate\Support\Facades\DB;

class ProvinceMapIdSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProvinceMapId::truncate();
        
        $mappings = [
            // Kode BPS (2 Digit NIK) => [MAP_ID, NAMA PROVINSI]
            ['11', [1, 'Aceh']],
            ['12', [33, 'Sumatera Utara']],
            ['13', [31, 'Sumatera Barat']], 
            ['14', [25, 'Riau']], 
            ['15', [9, 'Jambi']], 
            ['16', [32, 'Sumatera Selatan']], 
            ['17', [5, 'Bengkulu']], 
            ['18', [19, 'Lampung']], 
            ['19', [3, 'Bangka-Belitung']], 
            ['21', [18, 'Kepulauan Riau']], 
            
            // JAWA & BALI
            ['31', [8, 'Jakarta']], 
            ['32', [10, 'Jawa Barat']], 
            ['33', [11, 'Jawa Tengah']], 
            ['34', [34, 'Yogyakarta']], 
            ['35', [12, 'Jawa Timur']],
            ['36', [4, 'Banten']],
            ['51', [2, 'Bali']], 

            // NUSA TENGGARA
            ['52', [22, 'Nusa Tenggara Barat']], 
            ['53', [23, 'Nusa Tenggara Timur']],

            // KALIMANTAN
            ['61', [13, 'Kalimantan Barat']],
            ['62', [15, 'Kalimantan Tengah']],
            ['63', [14, 'Kalimantan Selatan']],
            ['64', [16, 'Kalimantan Timur']],
            ['65', [17, 'Kalimantan Utara']],

            // SULAWESI
            ['71', [30, 'Sulawesi Utara']], 
            ['72', [28, 'Sulawesi Tengah']],
            ['73', [27, 'Sulawesi Selatan']],
            ['74', [29, 'Sulawesi Tenggara']],
            ['75', [6, 'Gorontalo']], 
            ['76', [26, 'Sulawesi Barat']],

            // MALUKU & PAPUA
            ['81', [21, 'Maluku']], 
            ['82', [20, 'Maluku Utara']],
            ['91', [24, 'Papua']], 
            ['92', [7, 'Irian Jaya Barat']], 
        ];

        foreach ($mappings as $data) {
            ProvinceMapId::updateOrCreate(
                ['bps_code' => $data[0]],
                ['map_id' => $data[1][0], 'name' => $data[1][1]]
            );
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
