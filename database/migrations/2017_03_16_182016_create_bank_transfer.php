<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\BankTransfer\Entity as BankTransfer;
use RZP\Constants\Table;

class CreateBankTransfer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANK_TRANSFER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(BankTransfer::ID, BankTransfer::ID_LENGTH)
                  ->primary();

            $table->char(BankTransfer::PAYMENT_ID, BankTransfer::ID_LENGTH)
                  ->nullable();

            $table->char(BankTransfer::MERCHANT_ID, BankTransfer::ID_LENGTH)
                  ->nullable();

            $table->string(BankTransfer::PAYER_ACCOUNT, 20);

            $table->string(BankTransfer::PAYER_IFSC, 11);

            $table->string(BankTransfer::PAYEE_ACCOUNT, 20);

            $table->string(BankTransfer::PAYEE_IFSC, 11);

            $table->integer(BankTransfer::AMOUNT);

            $table->string(BankTransfer::MODE, 5);

            $table->string(BankTransfer::UTR, 30);

            $table->integer(BankTransfer::TIME);

            $table->text(BankTransfer::DESCRIPTION)
                  ->nullable();

            $table->integer(BankTransfer::CREATED_AT);
            $table->integer(BankTransfer::UPDATED_AT);

            $table->index(BankTransfer::UTR);
            $table->index(BankTransfer::PAYER_ACCOUNT);
            $table->index(BankTransfer::PAYEE_ACCOUNT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::BANK_TRANSFER);
    }
}
