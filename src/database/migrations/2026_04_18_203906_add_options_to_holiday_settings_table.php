<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holiday_settings', function (Blueprint $table) {
            $table->boolean('block_registration')->default(true)->after('note');
            $table->boolean('block_chart')->default(true)->after('block_registration');
        });
    }

    public function down(): void
    {
        Schema::table('holiday_settings', function (Blueprint $table) {
            $table->dropColumn(['block_registration', 'block_chart']);
        });
    }
};