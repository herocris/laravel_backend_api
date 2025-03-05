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
        Schema::create('weapon_confiscations', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');
            $table->string('photo');

            $table->unsignedBigInteger('confiscation_id');
            $table->unsignedBigInteger('weapon_id');

            $table->foreign('confiscation_id')
                  ->references('id')->on('confiscations')
                  ->onDelete('cascade');

            $table->foreign('weapon_id')
                  ->references('id')->on('weapons')
                  ->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weapon_confiscations');
    }
};
