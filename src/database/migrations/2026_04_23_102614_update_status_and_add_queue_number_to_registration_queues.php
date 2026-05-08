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
        Schema::table('registration_queues', function (Blueprint $table) {
            $table->string('queue_number', 5)->nullable()->after('registration_number');
            $table->string('status')->default('pending')->change();
        });

        DB::statement("ALTER TABLE registration_queues MODIFY COLUMN status ENUM('pending', 'checked_in', 'calling', 'serving', 'served', 'skipped', 'expired') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_queues', function (Blueprint $table) {
            $table->dropColumn('queue_number');
        });
    }
};
