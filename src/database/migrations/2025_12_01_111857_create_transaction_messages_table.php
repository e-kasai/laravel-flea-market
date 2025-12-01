<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionMessagesTable extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_messages', function (Blueprint $table) {
            $table->id();

            // どの取引のメッセージか
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();

            // 誰が送ったか
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // 本文
            $table->string('message', 400);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_messages');
    }
}
