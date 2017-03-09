<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Base\UniqueIdEntity;
use RZP\Gateway\Cybersource\Entity as Cybersource;
use RZP\Constants\Table;

class CreateCybersourceGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CYBERSOURCE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Cybersource::ID);

            $table->char(Cybersource::PAYMENT_ID, UniqueIdEntity::ID_LENGTH);

            $table->string(Cybersource::ACQUIRER, 10)
                  ->nullable();

            $table->char(Cybersource::ACTION, 10)
                  ->nullable();

            $table->integer(Cybersource::RECEIVED)
                  ->default(0);

            $table->char(Cybersource::REFUND_ID, UniqueIdEntity::ID_LENGTH)
                  ->nullable();

            $table->char(Cybersource::AUTH_DATA, 40)
                  ->nullable();

            $table->char(Cybersource::COMMERCE_INDICATOR, 20)
                  ->nullable();

            $table->integer(Cybersource::AMOUNT);

            $table->string(Cybersource::CURRENCY, 3)
                  ->nullable();

            $table->char(Cybersource::PARES_STATUS, 2)
                  ->nullable();

            $table->char(Cybersource::STATUS, 20);

            $table->char(Cybersource::XID, 40)
                  ->nullable();

            $table->char(Cybersource::AVS_CODE, 2)
                  ->nullable();

            $table->char(Cybersource::CARD_CATEGORY, 2)
                  ->nullable();

            $table->char(Cybersource::CARD_GROUP, 2)
                  ->nullable();

            $table->char(Cybersource::CV_CODE, 2)
                  ->nullable();

            $table->char(Cybersource::VERES_ENROLLED, 2)
                  ->nullable();

            $table->char(Cybersource::ECI, 2)
                  ->nullable();

            $table->char(Cybersource::COLLECTION_INDICATOR, 2)
                  ->nullable();

            $table->char(Cybersource::CAVV, 40)
                  ->nullable();

            $table->char(Cybersource::AUTHORIZATION_CODE, 6)
                  ->nullable();

            $table->char(Cybersource::RECEIPT_NUMBER, 6)
                  ->nullable();

            $table->char(Cybersource::REF, 40)
                  ->nullable();

            $table->char(Cybersource::CAPTURE_REF, 40)
                  ->nullable();

            $table->char(Cybersource::MERCHANT_ADVICE_CODE, 30)
                  ->nullable();

            $table->char(Cybersource::GATEWAY_TRANSACTION_ID, 30)
                  ->nullable();

            $table->char(Cybersource::PROCESSOR_RESPONSE, 10)
                  ->nullable();

            $table->integer(Cybersource::REASON_CODE)
                  ->nullable();

            $table->integer(Cybersource::CREATED_AT);

            $table->integer(Cybersource::UPDATED_AT);

            $table->foreign(Cybersource::PAYMENT_ID)
                  ->references(Cybersource::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->index(Cybersource::STATUS);

            $table->index(Cybersource::RECEIVED);

            $table->index(Cybersource::CREATED_AT);

            $table->index(Cybersource::REFUND_ID);

            $table->index(Cybersource::REF);

            $table->index(Cybersource::CAPTURE_REF);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::CYBERSOURCE, function($table)
        {
            $table->dropForeign(Table::CYBERSOURCE.'_'.Cybersource::PAYMENT_ID.'_foreign');
        });

        Schema::drop(Table::CYBERSOURCE);
    }
}
