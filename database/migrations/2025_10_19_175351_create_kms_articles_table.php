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
        Schema::create('kms_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255)->comment('Judul artikel KMS.');
            $table->longText('content')->comment('Isi lengkap artikel (mendukung HTML/Markdown).');
            $table->string('category', 100)->default('Kebijakan')->comment('Kategori artikel (Contoh: Kebijakan, Prosedur, FAQ).');
            $table->text('tags')->nullable()->comment('Kata kunci yang dipisahkan koma untuk pencarian.');
            $table->boolean('is_active')->default(true)->comment('Status publikasi artikel.');

            // Foreign Key (Siapa yang membuat/mengedit)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Index untuk pencarian cepat
            $table->index('category');
            $table->fullText(['title', 'content', 'tags']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kms_articles');
    }
};
