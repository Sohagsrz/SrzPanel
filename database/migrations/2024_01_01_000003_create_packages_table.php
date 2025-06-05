<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('billing_cycle')->default('monthly'); // monthly, quarterly, yearly
            $table->boolean('is_active')->default(true);
            
            // Resource Limits
            $table->integer('disk_space')->default(0); // in MB
            $table->integer('bandwidth')->default(0); // in GB
            $table->integer('domains')->default(0);
            $table->integer('subdomains')->default(0);
            $table->integer('email_accounts')->default(0);
            $table->integer('databases')->default(0);
            $table->integer('ftp_accounts')->default(0);
            
            // Features
            $table->boolean('ssl_enabled')->default(false);
            $table->boolean('backup_enabled')->default(false);
            $table->boolean('firewall_enabled')->default(false);
            $table->boolean('dns_management')->default(false);
            $table->boolean('cron_jobs')->default(false);
            $table->boolean('shell_access')->default(false);
            
            // PHP Settings
            $table->string('php_version')->default('8.1');
            $table->integer('max_execution_time')->default(30);
            $table->integer('memory_limit')->default(128);
            $table->integer('upload_max_filesize')->default(2);
            $table->integer('post_max_size')->default(8);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('packages');
    }
}; 