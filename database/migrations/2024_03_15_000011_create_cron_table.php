<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cron', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('command');
            $table->string('schedule');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run')->nullable();
            $table->timestamp('next_run')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('output')->nullable();
            $table->text('error')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cron');
    }
}; 