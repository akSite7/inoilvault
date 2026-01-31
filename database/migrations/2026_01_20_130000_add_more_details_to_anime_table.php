<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anime', function (Blueprint $table) {
            $table->date('season_date')->nullable()->after('source');
            $table->unsignedBigInteger('studio_id')->nullable()->after('season_date');
            $table->text('description')->nullable()->after('duration');
            $table->string('trailer_url')->nullable()->after('description');
            $table->json('frames')->nullable()->after('trailer_url');
            $table->unsignedBigInteger('main_character_id')->nullable()->after('frames');
            $table->string('main_voice_actor')->nullable()->after('main_character_id');

            $table->foreign('studio_id')->references('id')->on('studios')->nullOnDelete();
            $table->foreign('main_character_id')->references('id')->on('characters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('anime', function (Blueprint $table) {
            $table->dropForeign(['studio_id']);
            $table->dropForeign(['main_character_id']);
            $table->dropColumn([
                'season_date',
                'studio_id',
                'description',
                'trailer_url',
                'frames',
                'main_character_id',
                'main_voice_actor',
            ]);
        });
    }
};
