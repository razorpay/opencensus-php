<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\FundAccount\Validation\Entity as E;

class CreateFundAccountValidations extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FUND_ACCOUNT_VALIDATION, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(E::ID, E::ID_LENGTH)
                  ->primary();

            $table->string(E::RECEIPT, 255)
                  ->nullable();

            $table->char(E::FUND_ACCOUNT_ID, E::ID_LENGTH);

            $table->string(E::FUND_ACCOUNT_TYPE, 255);

            $table->char(E::MERCHANT_ID, E::ID_LENGTH);

            $table->string(E::STATUS, 255);

            $table->integer(E::FTS_TRANSFER_ID)
                  ->nullable();

            $table->string(E::ACCOUNT_STATUS, 255)
                  ->nullable();

            $table->string(E::REGISTERED_NAME, 255)
                  ->nullable();

            $table->string(E::UTR, 255)
                ->nullable();

            $table->char(E::TRANSACTION_ID, E::ID_LENGTH)
                  ->nullable();

            $table->integer(E::FEES)
                  ->unsigned()
                  ->nullable();

            $table->integer(E::TAX)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(E::AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->char(E::CURRENCY, Payment::CURRENCY_LENGTH)
                  ->nullable();

            $table->char(E::BATCH_FUND_TRANSFER_ID, E::ID_LENGTH)
                  ->nullable();

            $table->integer(E::RETRY_AT)
                  ->unsigned()
                  ->nullable();

            $table->tinyInteger(E::ATTEMPTS)
                  ->default(1)
                  ->nullable();

            $table->char(E::BALANCE_ID, E::ID_LENGTH)
                  ->nullable();

            $table->string(E::ERROR_CODE, 255)
                  ->nullable();

            $table->string(E::INTERNAL_ERROR_CODE, 255)
                  ->nullable();

            $table->text(E::ERROR_DESCRIPTION)
                  ->nullable();

            $table->text(E::NOTES);

            $table->integer(E::CREATED_AT);
            $table->integer(E::UPDATED_AT);

            $table->unique([E::RECEIPT, E::MERCHANT_ID]);

            $table->index(E::UPDATED_AT);
            $table->index(E::MERCHANT_ID);
            $table->index([E::FUND_ACCOUNT_ID, E::MERCHANT_ID]);
            $table->index([E::CREATED_AT, E::MERCHANT_ID]);
            $table->index(E::FTS_TRANSFER_ID);
            $table->index(E::RETRY_AT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::FUND_ACCOUNT_VALIDATION);
    }
}
