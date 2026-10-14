<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('date');

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->boolean('all_day')
                ->default(false);

            $table->string('reason')
                ->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_blocks');
    }
};