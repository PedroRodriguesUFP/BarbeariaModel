<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barber_service', function (Blueprint $table) {
        $table->id();
        $table->foreignId('barber_id')
        ->constrained()
        ->cascadeOnDelete();
        $table->foreignId('service_id')
        ->constrained()
        ->cascadeOnDelete();
        $table->unique(['barber_id', 'service_id']);
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barber_service');
    }
};
