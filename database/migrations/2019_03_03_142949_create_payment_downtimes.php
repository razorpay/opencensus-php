<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Payment\Downtime\Entity as PaymentDowntime;

class CreatePaymentDowntimes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENT_DOWNTIME, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(PaymentDowntime::ID, PaymentDowntime::ID_LENGTH)
                  ->primary();

            $table->string(PaymentDowntime::STATUS);

            $table->tinyInteger(PaymentDowntime::SCHEDULED)
                  ->default(0);

            $table->string(PaymentDowntime::METHOD, 30);

            $table->integer(PaymentDowntime::BEGIN);

            $table->integer(PaymentDowntime::END)
                  ->nullable();

            $table->char(PaymentDowntime::SEVERITY, 10);

            $table->char(PaymentDowntime::ISSUER, 15)
                  ->nullable();

            $table->char(PaymentDowntime::TYPE, 10)
                  ->nullable();

            $table->char(PaymentDowntime::NETWORK, 10)
                  ->nullable();

            $table->string(PaymentDowntime::VPA_HANDLE, 255)
                  ->nullable();

            $table->char(PaymentDowntime::AUTH_TYPE, 10)
                  ->nullable();

            $table->string(PaymentDowntime::PSP, 255)
                ->nullable();

            $table->char(PaymentDowntime::MERCHANT_ID, Merchant::ID_LENGTH)
                ->nullable();

            $table->integer(PaymentDowntime::CREATED_AT);

            $table->integer(PaymentDowntime::UPDATED_AT);

            $table->index(PaymentDowntime::BEGIN);
            $table->index(PaymentDowntime::END);
            $table->index(PaymentDowntime::METHOD);
            $table->index(PaymentDowntime::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::PAYMENT_DOWNTIME);
    }
}
