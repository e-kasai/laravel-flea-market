<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('from_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('to_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->tinyInteger('score'); // 1〜5
            $table->unique(['transaction_id', 'from_user_id']); // 1取引で1回だけ評価可能
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
}
