<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_de_constancias', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id_constancias')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('roles')->where('nombre', 'registrador')->update(['nombre' => 'usuario']);
    }

    public function down(): void
    {
        DB::table('roles')->where('nombre', 'usuario')->update(['nombre' => 'registrador']);

        Schema::table('solicitud_de_constancias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
