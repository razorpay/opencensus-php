<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\PayoutOutbox\Entity as PayoutOutbox;

class CreatePayoutOutboxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYOUT_OUTBOX, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(PayoutOutbox::ID, PayoutOutbox::ID_LENGTH);

            $table->json(PayoutOutbox::PAYOUT_DATA)
                ->nullable(false);

            $table->char(PayoutOutbox::MERCHANT_ID, PayoutOutbox::ID_LENGTH);

            $table->char(PayoutOutbox::USER_ID, PayoutOutbox::ID_LENGTH);

            $table->string(PayoutOutbox::REQUEST_TYPE, 14);

            $table->string(PayoutOutbox::SOURCE, 50);

            $table->string(PayoutOutbox::PRODUCT, 255)
                ->default('banking');

            $table->integer(PayoutOutbox::CREATED_AT);

            $table->integer(PayoutOutbox::EXPIRES_AT);

            $table->integer(PayoutOutbox::DELETED_AT)->nullable();

            $table->primary([PayoutOutbox::ID, PayoutOutbox::CREATED_AT]);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PAYOUT_OUTBOX);
    }
}
