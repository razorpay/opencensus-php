<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Payout\Entity as Payout;

class CreatePsPayouts extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_payouts', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Payout::ID, Payout::ID_LENGTH)
                  ->primary();

            $table->char(Payout::MERCHANT_ID, Payout::ID_LENGTH);

            $table->char(Payout::FUND_ACCOUNT_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->string(Payout::METHOD);

            $table->string(Payout::REFERENCE_ID, 255)
                  ->nullable();

            $table->char(Payout::BALANCE_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->char(Payout::USER_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->char(Payout::BATCH_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->char(Payout::IDEMPOTENCY_KEY, 30)
                  ->nullable();

            $table->char(Payout::PAYOUT_LINK_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->string(Payout::PURPOSE, 255);

            $table->string(Payout::NARRATION, 255)
                  ->nullable();

            $table->string(Payout::PURPOSE_TYPE, 255)
                  ->nullable();

            $table->unsignedBigInteger(Payout::AMOUNT);

            $table->char(Payout::CURRENCY);

            $table->text(Payout::NOTES)
                  ->nullable();

            $table->integer(Payout::FEES)
                  ->unsigned()
                  ->default(0);

            $table->integer(Payout::TAX)
                  ->unsigned()
                  ->default(0);

            $table->string(Payout::STATUS);

            $table->integer(Payout::FTS_TRANSFER_ID)
                  ->nullable();

            $table->char(Payout::TRANSACTION_ID, Payout::ID_LENGTH)
                  ->nullable()
                  ->unique();

            $table->string(Payout::CHANNEL, 255)
                  ->nullable();

            $table->string(Payout::UTR)
                  ->nullable();

            $table->string(Payout::FAILURE_REASON)
                  ->nullable();

            $table->string(Payout::REMARKS)
                  ->nullable();

            $table->char(Payout::PRICING_RULE_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->integer(Payout::SCHEDULED_AT)
                  ->nullable();

            $table->integer(Payout::QUEUED_AT)
                  ->nullable();

            // not there in PS as of now.
            $table->integer(Payout::SCHEDULED_ON)
                  ->nullable();

            $table->string(Payout::MODE, 30)
                  ->nullable();

            $table->string(Payout::FEE_TYPE, 255)
                  ->nullable();

            $table->tinyInteger(Payout::WORKFLOW_FEATURE)
                  ->nullable();

            $table->tinyInteger(Payout::ORIGIN)
                  ->default(1);

            $table->string(Payout::STATUS_CODE, 255)
                  ->nullable();

            $table->char(Payout::CANCELLATION_USER_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->char(Payout::STATUS_DETAILS_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->string(Payout::REGISTERED_NAME, 255)
                  ->nullable();

            $table->string(Payout::QUEUED_REASON, 255)
                  ->nullable();

            $table->bigInteger(Payout::ON_HOLD_AT)
                  ->nullable();

            $table->integer(Payout::CREATED_AT);

            $table->integer(Payout::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_payouts');
    }
}
