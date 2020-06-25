<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance\Entity as Balance;

class CreateBalance extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BALANCE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Balance::ID, Balance::ID_LENGTH)
                  ->primary();

            $table->char(Balance::MERCHANT_ID, Balance::ID_LENGTH)
                  ->nullable(); //remove this once the migration is done

            $table->string(Balance::TYPE, 255)
                  ->nullable();

            $table->char(Balance::CURRENCY, 3)
                  ->nullable(); //remove this once the migration is done

            $table->string(Balance::NAME, 255)
                  ->nullable();

            $table->bigInteger(Balance::BALANCE)
                  ->default(0);

            $table->bigInteger(Balance::LOCKED_BALANCE)
                  ->unsigned()
                  ->default(0);

            $table->bigInteger(Balance::ON_HOLD)
                  ->default(0);

            $table->bigInteger(Balance::AMOUNT_CREDITS)
                  ->default(0);

            $table->bigInteger(Balance::FEE_CREDITS)
                  ->default(0);

            $table->bigInteger(Balance::REWARD_FEE_CREDITS)
                  ->default(0);

            $table->bigInteger(Balance::REFUND_CREDITS)
                  ->default(0);

            $table->string(Balance::ACCOUNT_NUMBER, 255)
                  ->nullable();

            $table->string(Balance::ACCOUNT_TYPE, 255)
                  ->nullable();

            $table->string(Balance::CHANNEL, 255)
                  ->nullable();

            $table->integer(Balance::CREATED_AT);
            $table->integer(Balance::UPDATED_AT);

            $table->foreign(Balance::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index(Balance::CREATED_AT);
            $table->index(Balance::MERCHANT_ID);
            $table->index(Balance::ACCOUNT_NUMBER);
            $table->index(Balance::CHANNEL);
            $table->index([Balance::MERCHANT_ID, Balance::TYPE, Balance::UPDATED_AT]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::BALANCE);
    }
}
