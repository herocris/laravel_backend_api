<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_locks', function (Blueprint $table): void {
            $table->string('name')->primary();
            $table->unsignedBigInteger('version')->default(0);
        });

        DB::table('authorization_locks')->insert(['name' => 'api', 'version' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_locks');
    }
};
