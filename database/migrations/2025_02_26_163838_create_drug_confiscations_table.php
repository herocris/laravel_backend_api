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
        Schema::create('drug_confiscations', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');
            $table->double('weight');
            $table->string('photo');

            $table->unsignedBigInteger('confiscation_id');
            $table->unsignedBigInteger('drug_id');
            $table->unsignedBigInteger('drug_presentation_id');

            $table->foreign('confiscation_id')
                  ->references('id')->on('confiscations')
                  ->onDelete('cascade');

            $table->foreign('drug_id')
                  ->references('id')->on('drugs')
                  ->onDelete('cascade');

            $table->foreign('drug_presentation_id')
                  ->references('id')->on('drug_presentations')
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
        Schema::dropIfExists('drug_confiscations');
    }
};
