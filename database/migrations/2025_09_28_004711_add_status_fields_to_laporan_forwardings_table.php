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
        Schema::table('laporan_forwardings', function (Blueprint $table) {
            $table->string('lapor_status_code')->nullable()->after('complaint_id');
            $table->string('lapor_status_name')->nullable()->after('lapor_status_code');
            $table->dateTime('next_check_at')->nullable()->after('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_forwardings', function (Blueprint $table) {
            $table->dropColumn(['lapor_status_code', 'lapor_status_name', 'next_check_at']);
        });
    }
};
