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
        Schema::table('reports', function (Blueprint $table) {
            $table->foreign('reporter_id')->references('id')->on('reporters')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('report_id')->references('id')->on('reports')->onDelete('cascade');
        });

        Schema::table('reporters', function (Blueprint $table) {
            $table->foreign('ktp_document_id')->references('id')->on('documents')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['reporter_id', 'category_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['report_id']);
        });

        Schema::table('reporters', function (Blueprint $table) {
            $table->dropForeign(['ktp_document_id']);
        });
    }
};
