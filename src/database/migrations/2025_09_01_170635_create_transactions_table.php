<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')
                ->constrained()
                ->unique()
                ->onDelete('cascade');

            $table->unsignedBigInteger('buyer_id')->index();

            $table->unsignedBigInteger('seller_id')->index();

            $table->tinyInteger('status'); //1=取引中, 2=完了

            $table->tinyInteger('payment_method');
            $table->unsignedInteger('purchase_price');

            $table->char('shipping_postal_code', 8);
            $table->string('shipping_address', 255);
            $table->string('shipping_building', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
}
