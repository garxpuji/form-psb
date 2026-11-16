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
        Schema::create('routers', function (Blueprint $table): void {
            $table->engine('InnoDB');
            $table->id();
            $table->string('nama_router');
            $table->string('ip_host');
            $table->integer('api_port')->default(8728);
            $table->string('username');
            $table->text('password');
            $table->string('lan_interface')->nullable();
            $table->boolean('is_pppoe_server')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
