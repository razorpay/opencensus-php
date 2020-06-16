<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\UpiTransfer\Entity as UpiTransfer;

class CreateUpiTransfer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::UPI_TRANSFER, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(UpiTransfer::ID, UpiTransfer::ID_LENGTH)
                  ->primary();

            $table->char(UpiTransfer::PAYMENT_ID, UpiTransfer::ID_LENGTH)
                  ->nullable();

            $table->char(UpiTransfer::VIRTUAL_ACCOUNT_ID, UpiTransfer::ID_LENGTH)
                  ->nullable();

            $table->tinyInteger(UpiTransfer::EXPECTED)
                  ->default(0);

            $table->string(UpiTransfer::UNEXPECTED_REASON)
                  ->nullable()
                  ->default(null);

            $table->integer(UpiTransfer::AMOUNT)
                  ->unsigned();

            $table->string(UpiTransfer::PAYER_VPA, 255);

            $table->string(UpiTransfer::PAYER_ACCOUNT, 40)
                  ->nullable();

            $table->string(UpiTransfer::PAYER_IFSC, 13);

            $table->string(UpiTransfer::PAYER_BANK)
                  ->nullable();

            $table->string(UpiTransfer::PAYEE_VPA);

            $table->string(UpiTransfer::GATEWAY)
                  ->nullable();

            $table->string(UpiTransfer::GATEWAY_MERCHANT_ID)
                  ->nullable();

            $table->string(UpiTransfer::NPCI_REFERENCE_ID)
                  ->nullable();

            $table->string(UpiTransfer::PROVIDER_REFERENCE_ID)
                  ->nullable();

            $table->string(UpiTransfer::TRANSACTION_REFERENCE)
                  ->nullable();

            $table->string(UpiTransfer::TRANSACTION_TIME)
                  ->nullable();

            $table->integer(UpiTransfer::CREATED_AT);

            $table->integer(UpiTransfer::UPDATED_AT);


            $table->index(UpiTransfer::PAYMENT_ID);
            $table->index(UpiTransfer::VIRTUAL_ACCOUNT_ID);
            $table->index(UpiTransfer::PAYEE_VPA);
            $table->index(UpiTransfer::PAYER_VPA);
            $table->index(UpiTransfer::GATEWAY_MERCHANT_ID);
            $table->index(UpiTransfer::NPCI_REFERENCE_ID);
            $table->index(UpiTransfer::PROVIDER_REFERENCE_ID);
            $table->index(UpiTransfer::CREATED_AT);
            $table->index(UpiTransfer::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::UPI_TRANSFER);
    }
}
