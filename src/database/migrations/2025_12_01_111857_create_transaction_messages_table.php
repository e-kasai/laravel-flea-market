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

            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            // 送信者
            $table->unsignedBigInteger('user_id')->index();
            // 受信者
            $table->unsignedBigInteger('to_user_id')->index();

            $table->string('body', 400);
            $table->string('image_path')->nullable();
            // 未読フラグ
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_messages');
    }
}
