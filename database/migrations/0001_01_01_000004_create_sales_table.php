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
        Schema::create('sales', function (Blueprint $table): void {
            $table->engine('InnoDB');
            $table->id();
            $table->string('nama_sales');
            $table->string('kode_sales_ip');
            $table->string('kode_sales_pppoe');
            $table->foreignId('ip_router_id')
                ->constrained('routers')
                ->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
