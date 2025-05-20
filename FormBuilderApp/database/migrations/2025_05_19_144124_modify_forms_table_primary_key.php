<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // We will drop all tables and recreate them with consistent ID types
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('forms');
        
        // Create forms table with consistent primary key
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('identifier')->unique();
            $table->integer('version')->default(1);
            $table->boolean('is_published')->default(false);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('status')->default('draft'); // draft, published, archived
            $table->json('settings')->nullable(); // For additional form settings
            $table->json('form_builder_json');
            $table->string('custom_submit_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Create form_submissions table
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->integer('form_version')->default(1);
            $table->json('content');
            $table->string('submission_ip')->nullable();
            $table->json('files_meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_complete')->default(true);
            $table->boolean('is_anonymous')->default(false);
            $table->string('user_agent')->nullable();
            $table->string('status')->default('new'); // new, reviewed, approved, rejected
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('forms');
    }
};
