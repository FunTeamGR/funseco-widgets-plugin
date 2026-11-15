<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('visits')->default(0);
            // "<display name>|<IANA zone>" chosen in the clock's timezone window
            $table->string('timezone')->nullable();
            $table->boolean('display_widgets')->default(true);
            $table->unsignedInteger('finishes')->default(0);
            $table->integer('round_points')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_player_stats');
    }
};
