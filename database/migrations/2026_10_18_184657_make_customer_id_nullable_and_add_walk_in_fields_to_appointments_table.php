<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->change();

            $table->string('walk_in_name')->nullable()->after('customer_id');
            $table->string('walk_in_phone')->nullable()->after('walk_in_name');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['walk_in_name', 'walk_in_phone']);
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};