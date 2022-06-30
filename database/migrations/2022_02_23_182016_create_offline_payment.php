<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\OfflinePayment\Entity as OfflinePayment;
use RZP\Constants\Table;

class CreateOfflinePayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::OFFLINE_PAYMENT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(OfflinePayment::ID, OfflinePayment::ID_LENGTH)
                  ->primary();

            $table->string(OfflinePayment::CHALLAN_NUMBER, OfflinePayment::CHALLAN_LENGTH);

            $table->integer(OfflinePayment::AMOUNT);

            $table->string(OfflinePayment::MODE);

            $table->string(OfflinePayment::STATUS);

            $table->string(OfflinePayment::DESCRIPTION)
                  ->nullable();

            $table->string(OfflinePayment::CURRENCY)
                  ->default('INR');

            $table->string(OfflinePayment::BANK_REFERENCE_NUMBER)
                  ->nullable();

            $table->json(OfflinePayment::PAYMENT_INSTRUMENT_DETAILS)
                  ->nullable();

            $table->json(OfflinePayment::PAYER_DETAILS)
                  ->nullable();

            $table->string(OfflinePayment::PAYMENT_TIMESTAMP)
                  ->default('');

            $table->text(OfflinePayment::ADDITIONAL_INFO)
                  ->nullable();

            $table->string(OfflinePayment::CLIENT_CODE)
                  ->nullable();

            $table->string(OfflinePayment::MERCHANT_ID, OfflinePayment::ID_LENGTH);

            $table->string(OfflinePayment::VIRTUAL_ACCOUNT_ID, OfflinePayment::ID_LENGTH);

            $table->string(OfflinePayment::PAYMENT_ID, OfflinePayment::ID_LENGTH);



            $table->integer(OfflinePayment::CREATED_AT);
            $table->integer(OfflinePayment::UPDATED_AT);

            $table->integer(OfflinePayment::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->index(OfflinePayment::CHALLAN_NUMBER);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::OFFLINE_PAYMENT);
    }
}

