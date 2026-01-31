<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anime', function (Blueprint $table) {
            $table->unsignedBigInteger('related_anime_id')->nullable()->after('main_voice_actor');
            $table->string('related_type', 40)->nullable()->after('related_anime_id');

            $table->foreign('related_anime_id')->references('id')->on('anime')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('anime', function (Blueprint $table) {
            $table->dropForeign(['related_anime_id']);
            $table->dropColumn(['related_anime_id', 'related_type']);
        });
    }
};
