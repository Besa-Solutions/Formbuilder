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
        if (Schema::hasTable('form_submissions')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->integer('form_version')->default(1)->after('form_id');
                $table->string('submission_ip')->nullable()->after('content');
                $table->json('files_meta')->nullable()->after('submission_ip');
                $table->timestamp('started_at')->nullable()->after('files_meta');
                $table->timestamp('completed_at')->nullable()->after('started_at');
                $table->boolean('is_complete')->default(true)->after('completed_at');
                $table->boolean('is_anonymous')->default(false)->after('is_complete');
                $table->string('user_agent')->nullable()->after('is_anonymous');
                $table->string('status')->default('new')->after('user_agent');
            });
        } else {
            Schema::table('submissions', function (Blueprint $table) {
                $table->integer('form_version')->default(1)->after('form_id');
                $table->string('submission_ip')->nullable()->after('content');
                $table->json('files_meta')->nullable()->after('submission_ip');
                $table->timestamp('started_at')->nullable()->after('files_meta');
                $table->timestamp('completed_at')->nullable()->after('started_at');
                $table->boolean('is_complete')->default(true)->after('completed_at');
                $table->boolean('is_anonymous')->default(false)->after('is_complete');
                $table->string('user_agent')->nullable()->after('is_anonymous');
                $table->string('status')->default('new')->after('user_agent');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnsToRemove = [
            'form_version',
            'submission_ip',
            'files_meta',
            'started_at',
            'completed_at',
            'is_complete',
            'is_anonymous',
            'user_agent',
            'status'
        ];
        
        if (Schema::hasTable('form_submissions')) {
            Schema::table('form_submissions', function (Blueprint $table) use ($columnsToRemove) {
                $table->dropColumn($columnsToRemove);
            });
        } else {
            Schema::table('submissions', function (Blueprint $table) use ($columnsToRemove) {
                $table->dropColumn($columnsToRemove);
            });
        }
    }
};
