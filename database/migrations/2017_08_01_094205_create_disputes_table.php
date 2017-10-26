<?php

use RZP\Constants\Table;
use RZP\Models\Dispute\Status;
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

            $table->char(Dispute::PARENT_ID, Merchant::ID_LENGTH)
                ->nullable();

            $table->char(Dispute::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(Dispute::REASON_ID, Reason::ID_LENGTH);

            $table->char(Dispute::TRANSACTION_ID, Transaction::ID_LENGTH)
                  ->nullable();

            $table->integer(Dispute::AMOUNT)
                  ->unsigned();

            $table->char(Dispute::CURRENCY, Payment::CURRENCY_LENGTH);

            $table->integer(Dispute::AMOUNT_DEDUCTED)
                  ->unsigned()
                  ->default(0);

            $table->integer(Dispute::AMOUNT_REVERSED)
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

            $table->text(Dispute::COMMENTS)
                  ->nullable();

            $table->boolean(Dispute::DEDUCT_AT_ONSET)
                  ->default(1);

            $table->integer(Dispute::CREATED_AT);

            $table->integer(Dispute::UPDATED_AT);

            $table->integer(Dispute::RESOLVED_AT)
                  ->nullable();

            $table->integer(Dispute::RAISED_ON);

            $table->integer(Dispute::EXPIRES_ON);

            $table->index(Dispute::STATUS);
            $table->index(Dispute::PHASE);
            $table->index(Dispute::REASON_CODE);
            $table->index(Dispute::GATEWAY_DISPUTE_ID);
            $table->index(Dispute::CREATED_AT);
            $table->index(Dispute::RESOLVED_AT);

            $table->foreign(Dispute::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Dispute::PAYMENT_ID)
                  ->references(Payment::ID)
                  ->on(Table::PAYMENT)
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

            $table->dropForeign(Table::DISPUTE.'_'.Dispute::PAYMENT_ID.'_foreign');

            $table->dropForeign(Table::DISPUTE.'_'.Dispute::TRANSACTION_ID.'_foreign');

            $table->dropForeign(Table::DISPUTE.'_'.Dispute::REASON_ID.'_foreign');
        });

        Schema::drop(Table::DISPUTE);
    }
}

