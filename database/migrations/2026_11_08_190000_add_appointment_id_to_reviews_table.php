<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Marcação a que a avaliação se refere (opcional, para dados antigos).
            $table->foreignId('appointment_id')
                ->nullable()
                ->after('reviewer_id')
                ->constrained('appointments')
                ->nullOnDelete();

            // Uma marcação só pode ter uma avaliação ao barbeiro e uma ao serviço.
            $table->unique(['appointment_id', 'reviewable_type']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['appointment_id', 'reviewable_type']);
            $table->dropConstrainedForeignId('appointment_id');
        });
    }
};
