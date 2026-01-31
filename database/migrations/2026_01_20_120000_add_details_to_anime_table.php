<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anime', function (Blueprint $table) {
            $table->unsignedInteger('episodes')->nullable()->after('genres');
            $table->string('status', 60)->nullable()->after('episodes');
            $table->string('source', 60)->nullable()->after('status');
            $table->string('season', 60)->nullable()->after('source');
            $table->string('mpaa_rating', 30)->nullable()->after('season');
            $table->string('age_rating', 30)->nullable()->after('mpaa_rating');
            $table->string('duration', 60)->nullable()->after('age_rating');
        });
    }

    public function down(): void
    {
        Schema::table('anime', function (Blueprint $table) {
            $table->dropColumn([
                'episodes',
                'status',
                'source',
                'season',
                'mpaa_rating',
                'age_rating',
                'duration',
            ]);
        });
    }
};
