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

            $table->string(BankTransfer::PAYER_NAME)
                  ->nullable();

            $table->string(BankTransfer::PAYER_ACCOUNT, 20);

            $table->string(BankTransfer::PAYER_IFSC, 13);

            $table->char(BankTransfer::PAYER_BANK_ACCOUNT_ID, BankTransfer::ID_LENGTH)
                  ->nullable();

            $table->string(BankTransfer::PAYEE_ACCOUNT, 20);

            $table->string(BankTransfer::PAYEE_IFSC, 11);

            $table->char(BankTransfer::VIRTUAL_ACCOUNT_ID, BankTransfer::ID_LENGTH)
                  ->nullable();

            $table->integer(BankTransfer::AMOUNT);

            $table->string(BankTransfer::MODE, 5);

            $table->string(BankTransfer::UTR, 30);

            $table->bigInteger(BankTransfer::TIME);

            $table->text(BankTransfer::DESCRIPTION)
                  ->nullable();

            $table->tinyInteger(BankTransfer::EXPECTED)
                  ->default(0);

            $table->tinyInteger(BankTransfer::NOTIFIED)
                  ->default(0);

            $table->integer(BankTransfer::CREATED_AT);
            $table->integer(BankTransfer::UPDATED_AT);

            $table->foreign(BankTransfer::VIRTUAL_ACCOUNT_ID)
                  ->references('id')
                  ->on(Table::VIRTUAL_ACCOUNT)
                  ->on_delete('restrict');

            $table->foreign(BankTransfer::PAYMENT_ID)
                  ->references('id')
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->foreign(BankTransfer::MERCHANT_ID)
                  ->references('id')
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(BankTransfer::PAYER_BANK_ACCOUNT_ID)
                  ->references('id')
                  ->on(Table::BANK_ACCOUNT)
                  ->on_delete('restrict');

            $table->index(BankTransfer::UTR);
            $table->index(BankTransfer::PAYER_ACCOUNT);
            $table->index(BankTransfer::PAYEE_ACCOUNT);
            $table->index(BankTransfer::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::BANK_TRANSFER, function($table)
        {
            $table->dropForeign(Table::BANK_TRANSFER . '_' . BankTransfer::PAYMENT_ID . '_foreign');

            $table->dropForeign(Table::BANK_TRANSFER . '_' . BankTransfer::VIRTUAL_ACCOUNT_ID . '_foreign');

            $table->dropForeign(Table::BANK_TRANSFER . '_' . BankTransfer::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::BANK_TRANSFER);
    }
}
