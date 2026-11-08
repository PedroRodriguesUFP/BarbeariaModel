<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda o id da sessão da Stripe. É único: a mesma sessão
     * nunca gera dois pagamentos. Pagamentos manuais ficam com null.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_session_id')->nullable()->unique()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['stripe_session_id']);
            $table->dropColumn('stripe_session_id');
        });
    }
};
