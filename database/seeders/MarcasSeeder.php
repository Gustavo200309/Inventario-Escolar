<?php

namespace Database\Seeders;

use App\Models\Marca;
use Illuminate\Database\Seeder;

class MarcasSeeder extends Seeder
{
    public function run(): void
    {
        $marcas = [
            'HP',
            'Acer',
            'Dell',
            'Lenovo',
            'Samsung',
            'Sony',
            'Apple',
            'Microsoft',
            'Canon',
            'Epson',
            'Brother',
            'Panasonic',
            'LG',
            'Toshiba',
            'Honeywell',
            'Zebra',
            'Cisco',
            'TP-Link',
            'Logitech',
            'Kingston',
        ];

        foreach ($marcas as $nombre) {
            Marca::firstOrCreate(['nombre_marca' => $nombre]);
        }
    }
}
