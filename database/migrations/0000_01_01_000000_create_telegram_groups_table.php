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
        Schema::create('telegram_groups', function (Blueprint $table): void {
            $table->engine('InnoDB');
            $table->id();
            $table->string('nama_grup');
            $table->string('chat_id');
            $table->integer('thread_id')->nullable();
            $table->boolean('is_all_team')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_groups');
    }
};
