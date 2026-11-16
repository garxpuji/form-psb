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
            $table->dropForeign(['sales_id']);
            $table->unsignedBigInteger('sales_id')->nullable()->change();
            $table->foreign('sales_id')
                ->references('id')
                ->on('sales')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('psb_orders', function (Blueprint $table): void {
            $table->dropForeign(['sales_id']);
            $table->unsignedBigInteger('sales_id')->nullable(false)->change();
            $table->foreign('sales_id')
                ->references('id')
                ->on('sales')
                ->restrictOnDelete();
        });
    }
};
