<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->string('username');
            $table->string('password');
            $table->bigInteger('quota')->default(0);
            $table->bigInteger('used_quota')->default(0);
            $table->string('status')->default('active');
            $table->string('forward_to')->nullable();
            $table->boolean('autoresponder_enabled')->default(false);
            $table->string('autoresponder_subject')->nullable();
            $table->text('autoresponder_message')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['domain_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
}; 