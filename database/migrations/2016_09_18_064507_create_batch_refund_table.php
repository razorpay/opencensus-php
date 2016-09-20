<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\BatchRefund\Entity as BatchRefund;
use RZP\Models\Merchant;
use RZP\Models\Payment\Refund;


class CreateBatchRefundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BATCH_REFUND, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(BatchRefund::ID, BatchRefund::ID_LENGTH)
                  ->primary();

            $table->char(BatchRefund::MERCHANT_ID, BatchRefund::ID_LENGTH);

            $table->char(BatchRefund::UPLOAD_FILE_URL, BatchRefund::FILE_URL_LENGTH);

            $table->char(BatchRefund::DOWNLOAD_FILE_URL, BatchRefund::FILE_URL_LENGTH)
                  ->nullable();

            $table->char(BatchRefund::STATUS, BatchRefund::STATUS_LENGTH);

            $table->integer(BatchRefund::TOTAL_COUNT);

            $table->integer(BatchRefund::SUCCESS_COUNT)
                  ->nullable();

            $table->integer(BatchRefund::FAILURE_COUNT)
                  ->nullable();

            $table->integer(BatchRefund::ATTEMPTS)
                  ->default(0);

            $table->integer(BatchRefund::AMOUNT)
                  ->nullable();

            $table->text(BatchRefund::COMMENT)
                  ->nullable();

            $table->integer(BatchRefund::PROCESSED_AT)
                  ->nullable();

            $table->integer(BatchRefund::CREATED_AT);
            $table->integer(BatchRefund::UPDATED_AT);

            $table->foreign(BatchRefund::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });


        Schema::table(Table::REFUND, function($table)
        {
            $table->char(Refund\Entity::BATCH_REFUND_ID, BatchRefund::ID_LENGTH)
                  ->nullable()
                  ->after(Refund\Entity::TRANSACTION_ID);

            $table->foreign(Refund\Entity::BATCH_REFUND_ID)
                  ->references(BatchRefund::ID)
                  ->on(Table::BATCH_REFUND)
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
        Schema::table(Table::REFUND, function($table)
        {
            $table->dropForeign(Table::REFUND.'_'.Payment::BATCH_REFUND_ID.'_foreign');
        });

        Schema::table(Table::BATCH_REFUND, function($table)
        {
            $table->dropForeign(Table::BATCH_REFUND.'_'.BatchRefund::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::BATCH_REFUND);
    }
}
