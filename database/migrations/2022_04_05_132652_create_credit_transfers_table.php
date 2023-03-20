<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\CreditTransfer\Entity as CreditTransfer;

class CreateCreditTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CREDIT_TRANSFER, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(CreditTransfer::ID, CreditTransfer::ID_LENGTH)
                  ->primary();

            $table->char(CreditTransfer::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->char(CreditTransfer::BALANCE_ID, Balance\Entity::ID_LENGTH);

            $table->bigInteger(CreditTransfer::AMOUNT)
                  ->unsigned();

            $table->char(CreditTransfer::CURRENCY, 3);

            $table->char(CreditTransfer::TRANSACTION_ID, Transaction\Entity::ID_LENGTH)
                  ->nullable();

            $table->string(CreditTransfer::UTR,64)
                  ->nullable();

            $table->char(CreditTransfer::ENTITY_ID, CreditTransfer::ID_LENGTH)
                  ->nullable();

            $table->string(CreditTransfer::ENTITY_TYPE, 32)
                  ->nullable();

            $table->string(CreditTransfer::MODE, 32)
                  ->nullable();

            $table->string(CreditTransfer::CHANNEL, 32)
                  ->nullable();

            $table->string(CreditTransfer::DESCRIPTION, 255)
                  ->nullable();

            $table->string(CreditTransfer::STATUS,32)
                  ->nullable();

            $table->char(CreditTransfer::PAYER_MERCHANT_ID, Merchant\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(CreditTransfer::PAYER_USER_ID, User\Entity::ID_LENGTH)
                  ->nullable();

            $table->string(CreditTransfer::PAYER_NAME)
                  ->nullable();

            $table->string(CreditTransfer::PAYER_ACCOUNT, 40)
                  ->nullable();

            $table->string(CreditTransfer::PAYER_IFSC, 20)
                  ->nullable();

            $table->char(CreditTransfer::PAYEE_ACCOUNT_ID, CreditTransfer::ID_LENGTH)
                  ->nullable();

            $table->string(CreditTransfer::PAYEE_ACCOUNT_TYPE, 32)
                  ->nullable();

            $table->integer(CreditTransfer::CREATED_AT);

            $table->integer(CreditTransfer::UPDATED_AT);

            $table->integer(CreditTransfer::PROCESSED_AT)
                  ->nullable();

            $table->integer(CreditTransfer::FAILED_AT)
                  ->nullable();

            $table->index(CreditTransfer::BALANCE_ID);
            $table->index(CreditTransfer::MERCHANT_ID);
            $table->index(CreditTransfer::ENTITY_ID);
            $table->index(CreditTransfer::CREATED_AT);
            $table->index(CreditTransfer::PAYER_MERCHANT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::CREDIT_TRANSFER);
    }
}
