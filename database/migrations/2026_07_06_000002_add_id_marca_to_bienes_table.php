<?php

use App\Models\Marca;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bienes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_marca')->nullable()->after('marca');
            $table->foreign('id_marca')->references('id_marca')->on('marcas')->nullOnDelete();
        });

        DB::statement('UPDATE bienes SET marca = NULL WHERE marca = \'\'');

        $marcasExistentes = DB::table('bienes')
            ->whereNotNull('marca')
            ->where('marca', '<>', '')
            ->distinct()
            ->pluck('marca');

        foreach ($marcasExistentes as $nombreMarca) {
            $marca = Marca::firstOrCreate(['nombre_marca' => $nombreMarca]);
            DB::table('bienes')
                ->where('marca', $nombreMarca)
                ->update(['id_marca' => $marca->id_marca]);
        }
    }

    public function down(): void
    {
        Schema::table('bienes', function (Blueprint $table) {
            $table->dropForeign(['id_marca']);
            $table->dropColumn('id_marca');
        });
    }
};
