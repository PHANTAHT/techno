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
            
            // Info Dasar
            $table->string('title');                          
            $table->text('description')->nullable(); // Sudah menggunakan text, aman dari error limit!
            
            // Source (Sudah ada 'classroom')
            $table->enum('source', ['manual', 'moodle', 'gmail', 'calendar', 'classroom'])->default('manual');
            $table->string('source_id')->nullable();          
            
            // Status & Priority
            $table->enum('priority', ['urgent', 'high', 'medium', 'low'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
            $table->datetime('deadline')->nullable();          
            $table->string('course_name')->nullable();         
            
            // Link & Files (Digabung ke sini)
           // ... kode lainnya ...

            // Link & Files
            $table->string('url')->nullable();                 
            $table->string('file_url')->nullable();
            $table->string('file_path')->nullable(); 
            $table->string('file_name')->nullable(); 
            $table->string('file_type')->nullable(); 

            
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