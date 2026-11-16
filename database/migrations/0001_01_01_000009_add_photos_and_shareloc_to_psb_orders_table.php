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
        Schema::table('psb_orders', function (Blueprint $table): void {
            $table->string('shareloc', 500)->after('alamat');
            $table->string('foto_mac_ont')->after('nama_teknisi');
            $table->string('foto_odp')->after('foto_mac_ont');
            $table->string('foto_kertas_registrasi')->after('foto_odp');
            $table->string('foto_ktp_pelanggan')->after('foto_kertas_registrasi');
            $table->string('foto_rumah_pelanggan')->after('foto_ktp_pelanggan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('psb_orders', function (Blueprint $table): void {
            $table->dropColumn([
                'shareloc',
                'foto_mac_ont',
                'foto_odp',
                'foto_kertas_registrasi',
                'foto_ktp_pelanggan',
                'foto_rumah_pelanggan',
            ]);
        });
    }
};
