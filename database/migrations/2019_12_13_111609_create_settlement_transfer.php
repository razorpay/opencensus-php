<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement\Transfer\Entity as Transfer;

class CreateSettlementTransfer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT_TRANSFER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Transfer::ID, Transfer::ID_LENGTH)
                  ->primary();

            $table->char(Transfer::MERCHANT_ID, Transfer::ID_LENGTH);

            $table->char(Transfer::SOURCE_MERCHANT_ID, Transfer::ID_LENGTH);

            $table->char(Transfer::SETTLEMENT_ID, Transfer::ID_LENGTH);

            $table->char(Transfer::SETTLEMENT_TRANSACTION_ID, Transfer::ID_LENGTH);

            $table->char(Transfer::TRANSACTION_ID, Transfer::ID_LENGTH);

            $table->char(Transfer::BALANCE_ID, Transfer::ID_LENGTH);

            $table->char(Transfer::CURRENCY, 3);

            $table->bigInteger(Transfer::AMOUNT);

            $table->bigInteger(Transfer::FEE);

            $table->bigInteger(Transfer::TAX);

            $table->integer(Transfer::CREATED_AT);

            $table->integer(Transfer::UPDATED_AT);

            // TODO: finalize on index
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SETTLEMENT_TRANSFER);
    }
}
