<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\User;
use RZP\Models\Batch;
use RZP\Models\Pricing;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\PayoutLink;
use RZP\Models\FundAccount;
use RZP\Models\Merchant\Balance;
use RZP\Models\Payout\Entity as Payout;
use RZP\Models\FundTransfer\Batch as BatchFundTransfer;
use RZP\Models\Payout\WorkflowFeature as WorkflowFeature;

class CreatePayoutsTable extends Migration
{
    /**
    * Run the migrations.
    *
    * @return void
    */
    public function up()
    {
        Schema::create(Table::PAYOUT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Payout::ID, Payout::ID_LENGTH)
                  ->primary();

            $table->char(Payout::MERCHANT_ID, Payout::ID_LENGTH);

            $table->char(Payout::CUSTOMER_ID, Customer\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Payout::FUND_ACCOUNT_ID, FundAccount\Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Payout::METHOD);

            $table->string(Payout::REFERENCE_ID, 255)
                  ->nullable();

            $table->char(Payout::BALANCE_ID, Balance\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Payout::DESTINATION_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->char(Payout::DESTINATION_TYPE, 20)
                  ->nullable();

            $table->char(Payout::USER_ID, User\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Payout::BATCH_ID, Batch\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Payout::IDEMPOTENCY_KEY, Batch\Entity::IDEMPOTENCY_ID_LENGTH)
                  ->nullable();

            $table->char(Payout::PAYOUT_LINK_ID, PayoutLink\Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Payout::PURPOSE, 255);

            $table->string(Payout::NARRATION, 255)
                  ->nullable();

            $table->string(Payout::PURPOSE_TYPE, 255)
                  ->nullable();

            $table->unsignedBigInteger(Payout::AMOUNT);

            $table->char(Payout::CURRENCY);

            $table->char(Payout::PAYMENT_ID, Payment\Entity::ID_LENGTH)
                  ->nullable()
                  ->default(null);

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

            $table->string(Payout::TRANSACTION_TYPE, 255)
                  ->nullable();

            $table->char(Payout::BATCH_FUND_TRANSFER_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->string(Payout::CHANNEL, 255)
                  ->nullable();

            $table->integer(Payout::ATTEMPTS)
                  ->default(1);

            $table->string(Payout::UTR)
                  ->nullable();

            $table->string(Payout::FAILURE_REASON)
                  ->nullable();

            $table->string(Payout::RETURN_UTR)
                  ->nullable();

            $table->string(Payout::REMARKS)
                  ->nullable();

            $table->char(Payout::PRICING_RULE_ID, Pricing\Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Payout::SCHEDULED_AT)
                  ->nullable();

            $table->integer(Payout::PROCESSED_AT)
                  ->nullable();

            $table->integer(Payout::PENDING_AT)
                  ->nullable();

            $table->integer(Payout::REVERSED_AT)
                  ->nullable();

            $table->integer(Payout::FAILED_AT)
                  ->nullable();

            $table->integer(Payout::REJECTED_AT)
                  ->nullable();

            $table->integer(Payout::QUEUED_AT)
                  ->nullable();

            $table->integer(Payout::CANCELLED_AT)
                  ->nullable();

            $table->integer(Payout::INITIATED_AT)
                  ->nullable();

            $table->integer(Payout::TRANSFERRED_AT)
                  ->nullable();

            $table->integer(Payout::SETTLED_ON)
                  ->nullable();

            $table->integer(Payout::BATCH_SUBMITTED_AT)
                  ->nullable();

            $table->integer(Payout::CREATE_REQUEST_SUBMITTED_AT)
                  ->nullable();

            $table->integer(Payout::SCHEDULED_ON)
                  ->nullable();

            $table->string(Payout::TYPE, 30)
                  ->default('default');

            $table->string(Payout::MODE, 30)
                  ->nullable();

            $table->string(Payout::FEE_TYPE, 255)
                  ->nullable();

            $table->tinyInteger(Payout::WORKFLOW_FEATURE)
                  ->nullable();

            $table->tinyInteger(Payout::ORIGIN)
                  ->default(1);

            $table->tinyInteger(Payout::IS_PAYOUT_SERVICE)
                  ->default(0);

            $table->string(Payout::STATUS_CODE, 255)
                  ->nullable();

            $table->char(Payout::CANCELLATION_USER_ID, User\Entity::ID_LENGTH)
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

            $table->index(Payout::PROCESSED_AT);

            $table->index(Payout::PENDING_AT);

            $table->index(Payout::REVERSED_AT);

            $table->index(Payout::FAILED_AT);

            $table->index(Payout::REJECTED_AT);

            $table->index(Payout::INITIATED_AT);

            $table->index(Payout::QUEUED_AT);

            $table->index(Payout::CANCELLED_AT);

            $table->index(Payout::CREATED_AT);

            $table->index(Payout::SCHEDULED_AT);

            $table->index(Payout::SCHEDULED_ON);

            $table->index(Payout::METHOD);

            $table->index(Payout::STATUS);

            $table->index(Payout::MODE);

            $table->index(Payout::REFERENCE_ID);

            $table->index(Payout::BALANCE_ID, Payout::MERCHANT_ID);

            $table->index(Payout::FUND_ACCOUNT_ID);

            $table->index([Payout::MERCHANT_ID, Payout::CREATED_AT]);

            $table->index(Payout::USER_ID);

            $table->index(Payout::FTS_TRANSFER_ID);

            $table->index(Payout::PURPOSE);

            $table->index(Payout::PURPOSE_TYPE);

            $table->index(Payout::PAYOUT_LINK_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::PAYOUT, function($table)
        {
            $table->dropForeign(Table::PAYOUT . '_' . Payout::CUSTOMER_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT . '_' . Payout::MERCHANT_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT . '_' . Payout::BALANCE_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT . '_' . Payout::PAYMENT_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT . '_' . Payout::BATCH_FUND_TRANSFER_ID . '_foreign');
        });

        Schema::drop(Table::PAYOUT);
    }
}
