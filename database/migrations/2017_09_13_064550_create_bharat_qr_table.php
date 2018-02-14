<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\BharatQr\Entity as BharatQr;
use RZP\Models\VirtualAccount\Entity as VirtualAccount;

class CreateBharatQrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BHARAT_QR, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(BharatQr::ID, BharatQr::ID_LENGTH)
                  ->primary();

            $table->char(BharatQr::PAYMENT_ID, BharatQr::ID_LENGTH)
                  ->nullable();

            $table->char(BharatQr::VIRTUAL_ACCOUNT_ID, BharatQr::ID_LENGTH)
                  ->nullable();

            $table->tinyInteger(BharatQr::EXPECTED)
                  ->default(0);

            $table->string(BharatQr::GATEWAY)
                  ->nullable();

            $table->char(BharatQr::METHOD);

            $table->integer(BharatQr::AMOUNT)
                  ->unsigned();

            $table->string(BharatQr::VPA)
                  ->nullable();

            $table->char(BharatQr::CARD_FIRST6, 6)
                  ->nullable();

            $table->char(BharatQr::CARD_LAST4, 4)
                  ->nullable();

            $table->string(BharatQr::PROVIDER_REFERENCE_ID)
                  -> nullable();

            $table->string(BharatQr::MERCHANT_REFERENCE);

            $table->integer(BharatQr::CREATED_AT);

            $table->integer(BharatQr::UPDATED_AT);

            $table->index(BharatQr::PROVIDER_REFERENCE_ID);

            $table->index(BharatQr::MERCHANT_REFERENCE);

            $table->index(BharatQr::CREATED_AT);

            $table->foreign(BharatQr::VIRTUAL_ACCOUNT_ID)
                  ->references(VirtualAccount::ID)
                  ->on(Table::VIRTUAL_ACCOUNT)
                  ->on_delete('restrict');

            $table->foreign(BharatQr::PAYMENT_ID)
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
        Schema::table(Table::BHARAT_QR, function($table)
        {
            $table->dropForeign(Table::BHARAT_QR . '_' . BharatQr::VIRTUAL_ACCOUNT_ID . '_foreign');

            $table->dropForeign(Table::BHARAT_QR . '_' . BharatQr::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::BHARAT_QR);
    }
}
