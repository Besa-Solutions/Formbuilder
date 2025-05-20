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
        if (!Schema::hasTable('form_analytics') && Schema::hasTable('forms')) {
            Schema::create('form_analytics', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('form_id');
                $table->integer('form_version')->default(1);
                $table->date('date');
                $table->integer('views')->default(0);
                $table->integer('starts')->default(0);
                $table->integer('completions')->default(0);
                $table->integer('abandonments')->default(0);
                $table->json('abandonment_points')->nullable(); // Tracks where users dropped off
                $table->integer('average_completion_time')->nullable(); // In seconds
                $table->timestamps();
                
                // Create a unique constraint on form_id, form_version, and date
                $table->unique(['form_id', 'form_version', 'date']);
                
                // Add foreign key constraint
                $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_analytics');
    }
};
