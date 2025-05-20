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
        Schema::table('forms', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('identifier');
            $table->boolean('is_published')->default(false)->after('version');
            $table->timestamp('start_date')->nullable()->after('is_published');
            $table->timestamp('end_date')->nullable()->after('start_date');
            $table->string('status')->default('draft')->after('end_date'); // draft, published, archived
            $table->json('settings')->nullable()->after('status'); // For additional form settings
            $table->text('description')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn([
                'version', 
                'is_published', 
                'start_date', 
                'end_date', 
                'status', 
                'settings',
                'description'
            ]);
        });
    }
};
