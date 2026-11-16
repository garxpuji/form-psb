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
        Schema::create('router_ip_slots', function (Blueprint $table): void {
            $table->engine('InnoDB');
            $table->id();
            $table->foreignId('router_id')
                ->constrained('routers')
                ->cascadeOnDelete();
            $table->string('queue_mk_id');
            $table->string('ip_address');
            $table->string('queue_name');
            $table->enum('status', ['available', 'used'])->default('available');
            $table->unsignedBigInteger('psb_order_id')->nullable();
            $table->timestamps();

            $table->unique(['router_id', 'ip_address']);
            $table->index(['router_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('router_ip_slots');
    }
};
