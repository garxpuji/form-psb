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
        Schema::create('psb_orders', function (Blueprint $table): void {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('nama_pelanggan');
            $table->text('alamat');
            $table->string('no_tlp');
            $table->foreignId('odp_id')
                ->constrained('odps')
                ->restrictOnDelete();
            $table->unsignedTinyInteger('port');
            $table->foreignId('paket_id')
                ->constrained('pakets')
                ->restrictOnDelete();
            $table->string('mac_address');
            $table->string('brand_router');
            $table->string('jarak_kabel');
            $table->string('signal_odp');
            $table->string('signal_ont');
            $table->foreignId('sales_id')
                ->constrained('sales')
                ->restrictOnDelete();
            $table->string('nama_teknisi');
            $table->enum('tipe_koneksi', ['pppoe', 'static_ip']);
            $table->string('hasil_poe_ip')->nullable();
            $table->enum('status_sync', ['pending', 'success', 'failed'])->default('pending');
            $table->text('error_log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psb_orders');
    }
};
