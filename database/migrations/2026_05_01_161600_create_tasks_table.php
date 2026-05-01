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
    Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');                          // judul tugas
        $table->text('description')->nullable();          // deskripsi (boleh kosong)
        $table->enum('source', ['manual', 'moodle', 'gmail', 'calendar'])->default('manual');
        $table->string('source_id')->nullable();          // ID dari platform asal
        $table->enum('priority', ['urgent', 'high', 'medium', 'low'])->default('medium');
        $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
        $table->datetime('deadline')->nullable();          // deadline tugas
        $table->string('course_name')->nullable();         // nama mata kuliah
        $table->string('url')->nullable();                 // link tugas (Moodle, dll)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
