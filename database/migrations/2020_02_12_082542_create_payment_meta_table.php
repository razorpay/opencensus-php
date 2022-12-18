
<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Payment\PaymentMeta\Entity as PaymentMeta;
use RZP\Models\Merchant\FreshdeskTicket\Entity;

class CreatePaymentMetaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENT_META, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(PaymentMeta::ID, PaymentMeta::ID_LENGTH)
                ->primary();

            $table->char(PaymentMeta::PAYMENT_ID, PaymentMeta::ID_LENGTH)
                ->index();

            $table->bigInteger(PaymentMeta::GATEWAY_AMOUNT)
                ->unsigned()
                ->nullable();

            $table->char(PaymentMeta::GATEWAY_CURRENCY, 3)
                ->nullable();

            $table->decimal(PaymentMeta::FOREX_RATE, 20, 6)
                ->nullable();

            $table->tinyInteger(PaymentMeta::DCC_OFFERED)
                ->default(0);

            $table->decimal(PaymentMeta::DCC_MARK_UP_PERCENT, 5, 2)
                ->nullable();

            $table->string(PaymentMeta::ACTION)
                ->nullable();

            $table->string(PaymentMeta::REFERENCE_ID)
                ->nullable();

            $table->bigInteger(PaymentMeta::MISMATCH_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->string(PaymentMeta::MISMATCH_AMOUNT_REASON)
                  ->nullable();

            $table->tinyInteger(PaymentMeta::MCC_APPLIED)
                ->default(0);

            $table->decimal(PaymentMeta::MCC_FOREX_RATE,20,6)
                ->nullable();

            $table->decimal(PaymentMeta::MCC_MARK_DOWN_PERCENT,5,2)
                ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PAYMENT_META);
    }
}
