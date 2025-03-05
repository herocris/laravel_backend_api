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
        Schema::create('confiscations', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('observation');
            $table->string('direction');
            $table->string('department');
            $table->string('municipality');
            $table->double('latitude');
            $table->double('length');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confiscations');
    }
};
