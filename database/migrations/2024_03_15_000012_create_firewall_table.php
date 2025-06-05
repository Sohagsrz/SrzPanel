<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('firewall', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('action');
            $table->string('source');
            $table->string('destination');
            $table->string('port')->nullable();
            $table->string('protocol')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('last_modified')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('firewall');
    }
}; 