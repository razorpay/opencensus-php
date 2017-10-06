<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\BharatQr\Entity as BharatQr;
use RZP\Models\Qr\Entity as Qr;

class CreateQrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::QR, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Qr::ID, BharatQr::ID_LENGTH)
                  ->primary();

            $table->char(Qr::PAYMENT_ID, BharatQr::ID_LENGTH)
                  ->nullable();

            $table->char(Qr::BHARAT_QR_ID, Qr::ID_LENGTH)
                  ->nullable();

            $table->string(Qr::GATEWAY_MERCHANT_ID);

            $table->char(Qr::METHOD);

            $table->string(Qr::AMOUNT);

            $table->string(Qr::VPA)
                  ->nullable();

            $table->string(Qr::CARD_NUMBER)
                  ->nullable();

            $table->string(Qr::CARD_NETWORK)
                  ->nullable();

            $table->string(Qr::PROVIDER);

            $table->string(Qr::PROVIDER_REFERENCE_ID)
                  -> nullable();

            $table->string(Qr::MERCHANT_REFERENCE);

            $table->string(Qr::TRACE_NUMBER)
                  ->nullable();

            $table->string(Qr::RRN)
                  ->nullable();

            $table->string(Qr::TRANSACTION_TIME)
                  ->nullable();

            $table->string(Qr::TRANSACTION_DATE)
                  ->nullable();

            $table->string(Qr::GATEWAY_TERMINAL_ID)
                  ->nullable();

            $table->string(Qr::GATEWAY_TERMINAL_DESC)
                  ->nullable();

            $table->string(Qr::CUSTOMER_NAME)
                  ->nullable();

            $table->tinyInteger(Qr::RECEIVED)
                  ->default(0);

            $table->string(Qr::STATUS_CODE)
                  ->nullable();

            $table->integer(Qr::CREATED_AT);

            $table->integer(Qr::UPDATED_AT);

            $table->foreign(Qr::BHARAT_QR_ID)
                  ->references(BharatQr::ID)
                  ->on(Table::BHARAT_QR)
                  ->on_delete('restrict');

            $table->foreign(Qr::PAYMENT_ID)
                  ->references(Payment::ID)
                  ->on(Table::PAYMENT)
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
        Schema::table(Table::Qr, function($table)
        {
            $table->dropForeign(Table::QR . '_' . Qr::BHARAT_QR_ID . '_foreign');

            $table->dropForeign(Table::Payment . '_' . Qr::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::Qr);
    }
}
