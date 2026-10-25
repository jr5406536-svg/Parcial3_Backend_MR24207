<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla del diagrama físico: Solicitud_de_constancias.
     * Fecha, hora y estado se llenan solos al registrar.
     */
    public function up(): void
    {
        Schema::create('solicitud_de_constancias', function (Blueprint $table) {
            $table->id('id_constancias');
            $table->string('nombre_empleado', 150);
            $table->string('correo', 150);
            $table->string('movil', 20);
            $table->string('tipo_de_constancia', 100);
            $table->text('descripcion');
            $table->unsignedInteger('cantidad');
            $table->date('fecha_de_solicitud');
            $table->time('hora_de_solicitud');
            $table->boolean('estado_solicitud')->default(true);

            $table->index('fecha_de_solicitud');
            $table->index('estado_solicitud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_de_constancias');
    }
};
