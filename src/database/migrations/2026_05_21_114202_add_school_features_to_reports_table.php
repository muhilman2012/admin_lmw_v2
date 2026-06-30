<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('free_school_id')
                  ->nullable()
                  ->after('category_id')
                  ->constrained('master_free_schools')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['free_school_id']);
            $table->dropColumn('free_school_id');
        });
    }
};
