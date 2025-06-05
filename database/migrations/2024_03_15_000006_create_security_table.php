<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('security', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('severity');
            $table->text('description');
            $table->string('source');
            $table->string('status');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('details')->nullable();
            $table->text('recommendation')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('security');
    }
}; 