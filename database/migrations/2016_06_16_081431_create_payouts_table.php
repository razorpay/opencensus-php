<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Payout\Entity as Payout;
use RZP\Models\Transaction;

class CreatePayoutsTable extends Migration
{
    /**
    * Run the migrations.
    *
    * @return void
    */
    public function up()
    {
        Schema::create(Table::PAYOUT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Payout::ID, Payout::ID_LENGTH)
                  ->primary();

            $table->char(Payout::MERCHANT_ID, Payout::ID_LENGTH);

            $table->char(Payout::CUSTOMER_ID, Payout::ID_LENGTH);

            $table->string(Payout::METHOD);

            $table->char(Payout::DESTINATION, Payout::ID_LENGTH);

            $table->char(Payout::TYPE, 20);

            $table->integer(Payout::AMOUNT)
                ->unsigned();

            $table->char(Payout::CURRENCY);

            $table->string(Payout::NOTES)
                ->nullable();

            $table->integer(Payout::FEE)
                  ->unsigned()
                  ->nullable();

            $table->integer(Payout::SERVICE_TAX)
                  ->unsigned()
                  ->nullable();

            $table->string(Payout::STATUS);

            $table->char(Payout::TRANSACTION_ID, Payout::ID_LENGTH)
                  ->nullable()
                  ->unique();

            $table->string(Payout::CHANNEL, 8);

            $table->string(Payout::UTR)
                  ->nullable()
                  ->unique();

            $table->string(Payout::FAILURE_REASON)
                  ->nullable();

            $table->string(Payout::RETURN_UTR)
                  ->nullable()
                  ->unique();

            $table->integer(Payout::CREATED_AT);

            $table->integer(Payout::UPDATED_AT);

            $table->index(Payout::CREATED_AT);

            $table->foreign(Payout::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Payout::CUSTOMER_ID)
                  ->references(Customer\Entity::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');

            $table->foreign(Payout::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::PAYOUT, function($table)
        {
            $table->dropForeign(Table::PAYOUT.'_'.Payout::CUSTOMER_ID.'_foreign');

            $table->dropForeign(Table::PAYOUT.'_'.Payout::MERCHANT_ID.'_foreign');

            $table->dropForeign(Table::PAYOUT.'_'.Payout::TRANSACTION_ID.'_foreign');
        });

        Schema::drop(Table::PAYOUT);

    }
}
