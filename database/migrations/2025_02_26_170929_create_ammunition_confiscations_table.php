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
        Schema::create('ammunition_confiscations', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');
            $table->string('photo');

            $table->unsignedBigInteger('confiscation_id');
            $table->unsignedBigInteger('ammunition_id');

            $table->foreign('confiscation_id')
                  ->references('id')->on('confiscations')
                  ->onDelete('cascade');

            $table->foreign('ammunition_id')
                  ->references('id')->on('ammunitions')
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
        Schema::dropIfExists('ammunition_confiscations');
    }
};
