<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Notification\Entity as Notification;

class CreateNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create(Table::NOTIFICATION, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Notification::ID);

            $table->char(Notification::ORDER_ID, Payment::ID_LENGTH);

            $table->char(Notification::TOKEN_ID, Payment::ID_LENGTH);

            $table->char(Notification::MERCHANT_ID, Payment::ID_LENGTH);

            $table->integer(Notification::PAYMENT_AFTER);

            $table->string(Notification::VPA)
                ->nullable();

            $table->string(Notification::GATEWAY)
                ->nullable();

            $table->string(Notification::PROVIDER, 50)
                ->nullable();

            $table->string(Notification::GATEWAY_MERCHANT_ID)
                ->nullable();

            $table->string(Notification::BANK_RRN)
                ->nullable();

            $table->string(Notification::STATUS);

            $table->string(Notification::NPCI_TXN_ID)
                ->nullable();

            $table->text(Notification::GATEWAY_REQUEST)
                ->nullable();

            $table->text(Notification::GATEWAY_RESPONSE)
                ->nullable();

            $table->integer(Notification::DELETED_AT)
                ->nullable();

            $table->integer(Notification::CREATED_AT);
            $table->integer(Notification::UPDATED_AT);

            $table->index(Notification::ORDER_ID);
            $table->index(Notification::TOKEN_ID);
            $table->index(Notification::MERCHANT_ID);
            $table->index(Notification::CREATED_AT);
            $table->index(Notification::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::NOTIFICATION);
    }
}
