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
        if (!Schema::hasTable('form_versions')) {
            Schema::create('form_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('form_id');
                $table->integer('version_number');
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('form_builder_json');
                $table->json('settings')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
                
                // Create a unique constraint on form_id and version_number
                $table->unique(['form_id', 'version_number']);
                
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
        Schema::dropIfExists('form_versions');
    }
};
