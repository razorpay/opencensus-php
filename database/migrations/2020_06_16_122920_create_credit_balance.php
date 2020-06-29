<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Credits\Balance\Entity as Balance;

class CreateCreditBalance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CREDIT_BALANCE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Balance::ID, Balance::ID_LENGTH)
                  ->primary();

            $table->char(Balance::MERCHANT_ID, Balance::ID_LENGTH);

            $table->string(Balance::TYPE, 255)
                  ->nullable();

            $table->string(Balance::PRODUCT, 255)
                  ->nullable();

            $table->bigInteger(Balance::BALANCE)
                  ->default(0);

            $table->integer(Balance::EXPIRED_AT)
                  ->nullable();

            $table->string(Balance::REFERENCE1, 255)
                  ->nullable();

            $table->string(Balance::REFERENCE2, 255)
                  ->nullable();

            $table->string(Balance::REFERENCE3, 255)
                  ->nullable();

            $table->string(Balance::REFERENCE4, 255)
                  ->nullable();

            $table->integer(Balance::CREATED_AT);

            $table->integer(Balance::UPDATED_AT);
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
