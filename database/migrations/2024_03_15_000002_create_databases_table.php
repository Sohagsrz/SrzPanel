<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('username');
            $table->string('password');
            $table->bigInteger('size')->default(0);
            $table->string('status')->default('active');
            $table->timestamp('last_backup_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('databases');
    }
}; 