<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Batch\Entity as Batch;
use RZP\Models\Merchant;
use RZP\Models\Payment\Refund;


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

            $table->char(Batch::UPLOAD_FILE_URL, Batch::FILE_URL_LENGTH);

            $table->char(Batch::DOWNLOAD_FILE_URL, Batch::FILE_URL_LENGTH)
                  ->nullable();

            $table->char(Batch::TYPE, Batch::ID_LENGTH);

            $table->char(Batch::STATUS, Batch::STATUS_LENGTH);

            $table->integer(Batch::TOTAL_COUNT);

            $table->integer(Batch::SUCCESS_COUNT)
                  ->nullable();

            $table->integer(Batch::FAILURE_COUNT)
                  ->nullable();

            $table->integer(Batch::ATTEMPTS)
                  ->default(0);

            $table->bigInteger(Batch::AMOUNT)
                  ->nullable();

            $table->bigInteger(Batch::PROCESSED_AMOUNT)
                  ->default(0);

            $table->text(Batch::COMMENT)
                  ->nullable();

            $table->integer(Batch::PROCESSED_AT)
                  ->nullable();

            $table->integer(Batch::CREATED_AT);
            $table->integer(Batch::UPDATED_AT);

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
            $table->dropForeign(Table::REFUND.'_'.Payment::BATCH_ID.'_foreign');
        });

        Schema::table(Table::BATCH, function($table)
        {
            $table->dropForeign(Table::BATCH.'_'.Batch::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::BATCH);
    }
}
