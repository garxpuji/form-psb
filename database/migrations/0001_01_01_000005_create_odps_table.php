<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('odps', function (Blueprint $table): void {
            $table->engine('InnoDB');
            $table->id();
            $table->string('nama_odp')->unique();
            $table->string('kode_odp');
            $table->enum('status_upgrade', ['upgrade', 'belum_upgrade'])->default('belum_upgrade');
            $table->unsignedInteger('last_seq')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odps');
    }
};
