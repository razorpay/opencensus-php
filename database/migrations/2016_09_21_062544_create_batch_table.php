<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Payout;
use RZP\Models\Invoice;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Payment\Refund;
use RZP\Models\Batch\Entity as Batch;


class CreateBatchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BATCH, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Batch::ID, Batch::ID_LENGTH)
                  ->primary();

            $table->char(Batch::MERCHANT_ID, Batch::ID_LENGTH);

            $table->string(Batch::NAME, 255)
                  ->nullable();

            $table->char(Batch::TYPE, 25);

            $table->string(Batch::SUB_TYPE, 25)
                  ->nullable();

            $table->string(Batch::GATEWAY, 25)
                  ->nullable();

            $table->text(Batch::FAILURE_REASON)
                  ->nullable();

            $table->char(Batch::STATUS, Batch::STATUS_LENGTH);

            $table->tinyInteger(Batch::PROCESSING)
                  ->default(0);

            $table->integer(Batch::TOTAL_COUNT);

            $table->integer(Batch::PROCESSED_COUNT)
                  ->nullable();

            $table->integer(Batch::SUCCESS_COUNT)
                  ->nullable();

            $table->integer(Batch::FAILURE_COUNT)
                  ->nullable();

            $table->integer(Batch::ATTEMPTS)
                  ->default(0);

            $table->bigInteger(Batch::AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(Batch::PROCESSED_AMOUNT)
                  ->unsigned()
                  ->default(0);

            $table->text(Batch::COMMENT)
                  ->nullable();

            $table->char(Batch::CREATOR_ID, 14)
                  ->nullable();

            $table->string(Batch::CREATOR_TYPE, 255)
                  ->nullable();

            $table->integer(Batch::PROCESSED_AT)
                  ->nullable();

            $table->integer(Batch::CREATED_AT);
            $table->integer(Batch::UPDATED_AT);

            $table->index(Batch::CREATED_AT);
            $table->index(Batch::TYPE);
            $table->index(Batch::GATEWAY);
            $table->index(Batch::STATUS);
            $table->index(Batch::CREATOR_ID);

            $table->foreign(Batch::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });

        Schema::table(Table::REFUND, function($table)
        {
            $table->char(Refund\Entity::BATCH_ID, Batch::ID_LENGTH)
                  ->nullable()
                  ->after(Refund\Entity::TRANSACTION_ID);

            $table->foreign(Refund\Entity::BATCH_ID)
                  ->references(Batch::ID)
                  ->on(Table::BATCH)
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
            $table->dropForeign(Table::REFUND . '_' . Refund\Entity::BATCH_ID . '_foreign');

            $table->dropColumn(Refund\Entity::BATCH_ID);
        });

        Schema::table(Table::BATCH, function($table)
        {
            $table->dropForeign(Table::BATCH . '_' . Batch::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::BATCH);
    }
}
