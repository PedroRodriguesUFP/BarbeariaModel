<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    
    // up serve para criar a tabela clients no banco de dados, com as colunas id, user_id, phone, tax_id e timestamps
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id(); // chave primária da tabela
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->onUpdate('cascade'); // chaves estrangeiras para a tabela users, com onDelete e onUpdate em cascade
            $table->string('phone')->nullable();
            $table->string('tax_id')->nullable();
            $table->timestamps(); // colunas created_at e updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    
    // down serve para deletar a tabela clients do banco de dados
    public function down(): void
    {
        Schema::dropIfExists('clients');// deleta a tabela clients caso ela exista
    }
};
