<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mod_notes', function (Blueprint $table) {
            // Menambahkan kolom untuk path file di MinIO dan nama asli file
            $table->string('attachment_path')->nullable()->after('note');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });
    }

    public function down()
    {
        Schema::table('mod_notes', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name']);
        });
    }
};
