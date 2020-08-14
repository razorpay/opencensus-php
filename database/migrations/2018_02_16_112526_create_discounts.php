<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Offer\Entity as Offer;
use RZP\Models\Order\Entity as Order;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Discount\Entity as Discount;

class CreateDiscounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::DISCOUNT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Discount::ID, Discount::ID_LENGTH)
                  ->primary();

            $table->char(Discount::PAYMENT_ID, Discount::ID_LENGTH);

            $table->char(Discount::ORDER_ID, Discount::ID_LENGTH)
                  ->nullable()
                  ->default(null);

            $table->char(Discount::OFFER_ID, Discount::ID_LENGTH)
                  ->nullable()
                  ->default(null);

            $table->integer(Discount::AMOUNT);

            $table->integer(Discount::CREATED_AT);
            $table->integer(Discount::UPDATED_AT);

            $table->foreign(Discount::PAYMENT_ID)
                  ->references(Payment::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->foreign(Discount::ORDER_ID)
                  ->references(Order::ID)
                  ->on(Table::ORDER)
                  ->on_delete('restrict');

            $table->foreign(Discount::OFFER_ID)
                  ->references(Offer::ID)
                  ->on(Table::OFFER)
                  ->on_delete('restrict');

            $table->index(Discount::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::DISCOUNT, function($table)
        {
            $table->dropForeign(Table::DISCOUNT . '_' . Discount::PAYMENT_ID . '_foreign');

            $table->dropForeign(Table::DISCOUNT . '_' . Discount::ORDER_ID . '_foreign');

            $table->dropForeign(Table::DISCOUNT . '_' . Discount::OFFER_ID . '_foreign');
        });

        Schema::drop(Table::DISCOUNT);
    }
}
