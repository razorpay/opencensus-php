<?php

use RZP\Constants\Table;
use RZP\Models\Dispute\Status;
use RZP\Models\Dispute\InternalStatus;
use RZP\Models\Dispute\Entity as Dispute;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Dispute\Reason\Entity as Reason;
use RZP\Models\Transaction\Entity as Transaction;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDisputesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::DISPUTE, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Dispute::ID, Dispute::ID_LENGTH)
                  ->primary();

            $table->char(Dispute::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(Dispute::PARENT_ID, Dispute::ID_LENGTH)
                  ->nullable();

            $table->char(Dispute::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(Dispute::REASON_ID, Reason::ID_LENGTH);

            $table->char(Dispute::TRANSACTION_ID, Transaction::ID_LENGTH)
                  ->nullable();

            $table->bigInteger(Dispute::AMOUNT)
                  ->unsigned();

            $table->char(Dispute::CURRENCY, Payment::CURRENCY_LENGTH);

            $table->bigInteger(Dispute::BASE_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->char(Dispute::BASE_CURRENCY, Payment::CURRENCY_LENGTH)
                  ->nullable();

            $table->bigInteger(Dispute::GATEWAY_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->char(Dispute::GATEWAY_CURRENCY, Payment::CURRENCY_LENGTH)
                  ->nullable();

            $table->bigInteger(Dispute::CONVERSION_RATE)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(Dispute::AMOUNT_DEDUCTED)
                  ->unsigned()
                  ->default(0);

            $table->bigInteger(Dispute::AMOUNT_REVERSED)
                  ->unsigned()
                  ->default(0);

            $table->string(Dispute::REASON_CODE, 255);

            $table->string(Dispute::REASON_DESCRIPTION);

            $table->string(Dispute::GATEWAY_DISPUTE_ID, 50)
                  ->nullable();

            $table->string(Dispute::GATEWAY_DISPUTE_STATUS, 255)
                  ->nullable();

            $table->string(Dispute::PHASE, 50);

            $table->string(Dispute::STATUS, 50)
                  ->default(Status::OPEN);

            $table->string(Dispute::INTERNAL_STATUS, 50)
                  ->default(InternalStatus::OPEN);

            $table->integer(Dispute::INTERNAL_RESPOND_BY)
                  ->nullable();

            $table->text(Dispute::COMMENTS)
                  ->nullable();

            $table->boolean(Dispute::DEDUCT_AT_ONSET)
                  ->default(1);

            $table->string(Dispute::EMAIL_NOTIFICATION_STATUS, 50)
                  ->nullable();

            $table->char(Dispute::DEDUCTION_SOURCE_ID, Dispute::DEDUCTION_SOURCE_ID_LENGTH)
                   ->nullable();

            $table->string(Dispute::DEDUCTION_SOURCE_TYPE, Dispute::DEDUCTION_SOURCE_TYPE_LENGTH)
                   ->nullable();

            $table->integer(Dispute::DEDUCTION_REVERSAL_AT)
                   ->nullable();

            $table->integer(Dispute::CREATED_AT);

            $table->integer(Dispute::UPDATED_AT);

            $table->integer(Dispute::RESOLVED_AT)
                  ->nullable();

            $table->integer(Dispute::RAISED_ON);

            $table->integer(Dispute::EXPIRES_ON);

            $table->json(Dispute::LIFECYCLE)
                  ->nullable();

            $table->index(Dispute::STATUS);
            $table->index(Dispute::PHASE);
            $table->index(Dispute::REASON_CODE);
            $table->index(Dispute::GATEWAY_DISPUTE_ID);
            $table->index(Dispute::CREATED_AT);
            $table->index(Dispute::UPDATED_AT);
            $table->index(Dispute::RESOLVED_AT);
            $table->index(Dispute::AMOUNT);
            $table->index([Dispute::EXPIRES_ON, Dispute::EMAIL_NOTIFICATION_STATUS]);
            $table->index(Dispute::INTERNAL_RESPOND_BY);

            $table->foreign(Dispute::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Dispute::TRANSACTION_ID)
                  ->references(Transaction::ID)
                  ->on(Table::TRANSACTION)
                  ->on_delete('restrict');

            $table->foreign(Dispute::REASON_ID)
                  ->references(Reason::ID)
                  ->on(Table::DISPUTE_REASON)
                  ->on_delete('restrict');
        });

        Schema::table(Table::DISPUTE, function(Blueprint $table)
        {
            $table->foreign(Dispute::PARENT_ID)
                  ->references(Dispute::ID)
                  ->on(Table::DISPUTE)
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
        Schema::table(Table::DISPUTE, function($table)
        {
            $table->dropForeign(Table::DISPUTE.'_'.Dispute::MERCHANT_ID.'_foreign');

            $table->dropForeign(Table::DISPUTE.'_'.Dispute::PARENT_ID . '_foreign');

            $table->dropForeign(Table::DISPUTE.'_'.Dispute::PAYMENT_ID.'_foreign');

            $table->dropForeign(Table::DISPUTE.'_'.Dispute::TRANSACTION_ID.'_foreign');

            $table->dropForeign(Table::DISPUTE.'_'.Dispute::REASON_ID.'_foreign');
        });

        Schema::drop(Table::DISPUTE);
    }
}

