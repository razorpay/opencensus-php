<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Constants\Table;

class CreateUpi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create(Table::UPI, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Upi::ID);

            $table->string(Upi::GATEWAY)
                ->nullable();

            $table->char(Upi::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(Upi::REFUND_ID, Payment::ID_LENGTH)
                  ->nullable();

            $table->string(Upi::ACTION);

            $table->string(Upi::TYPE)
                  ->nullable();

            $table->string(Upi::AMOUNT);

            $table->string(Upi::ACQUIRER, 20);

            $table->string(Upi::BANK, 5)
                  ->nullable();

            $table->string(Upi::PROVIDER, 50)
                  ->nullable();

            $table->string(Upi::CONTACT)
                  ->nullable();

            $table->string(Upi::GATEWAY_DATA)
                  ->nullable();

            $table->string(Upi::VPA)
                  ->nullable();

            $table->string(Upi::ACCOUNT_NUMBER)
                  ->nullable();

            $table->string(Upi::IFSC)
                  ->nullable();

            $table->string(Upi::NAME)
                  ->nullable();

            $table->mediumInteger(Upi::EXPIRY_TIME)
                  ->nullable();

            $table->tinyInteger(Upi::RECEIVED)
                  ->default(0);

            $table->string(Upi::MERCHANT_REFERENCE)
                  ->nullable();

            $table->string(Upi::GATEWAY_MERCHANT_ID)
                  ->nullable();

            $table->string(Upi::GATEWAY_PAYMENT_ID)
                  ->nullable();

            $table->string(Upi::STATUS_CODE)
                  ->nullable();

            $table->string(Upi::NPCI_REFERENCE_ID, 20)
                  ->nullable();

            $table->string(Upi::NPCI_TXN_ID)
                  ->nullable();

            $table->integer(Upi::RECONCILED_AT)
                  ->nullable();

            $table->integer(Upi::CREATED_AT);
            $table->integer(Upi::UPDATED_AT);

            $table->text(Upi::GATEWAY_ERROR)
                ->nullable();

            $table->index(Upi::REFUND_ID);
            $table->index(Upi::RECEIVED);
            $table->index(Upi::GATEWAY_PAYMENT_ID);
            $table->index(Upi::BANK);
            $table->index(Upi::STATUS_CODE);
            $table->index(Upi::NPCI_REFERENCE_ID);
            $table->index(Upi::CREATED_AT);
            $table->index([Upi::GATEWAY, Upi::RECONCILED_AT]);
            $table->index(Upi::NPCI_TXN_ID);
            $table->index(Upi::MERCHANT_REFERENCE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::UPI, function($table)
        {
            $table->dropForeign(Table::UPI.'_'.Upi::PAYMENT_ID.'_foreign');
        });

        Schema::drop(Table::UPI);
    }
}
