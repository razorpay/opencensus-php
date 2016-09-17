<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\RefundFile\Entity as RefundFile;
use RZP\Models\Merchant;


class CreateRefundFileTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::REFUND_FILE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(RefundFile::ID, RefundFile::ID_LENGTH)
                  ->primary();

            $table->char(RefundFile::MERCHANT_ID, RefundFile::ID_LENGTH);

            $table->char(RefundFile::UPLOAD_FILE_URL, RefundFile::FILE_URL_LENGTH);

            $table->char(RefundFile::DOWNLOAD_FILE_URL, RefundFile::FILE_URL_LENGTH)
                  ->nullable()
                  ->default(null);

            $table->char(RefundFile::STATUS, RefundFile::STATUS_LENGTH);

            $table->integer(RefundFile::TOTAL_COUNT);

            $table->integer(RefundFile::SUCCESS_COUNT)
                  ->nullable()
                  ->default(null);

            $table->integer(RefundFile::FAILURE_COUNT)
                  ->nullable()
                  ->default(null);
                  
            $table->integer(RefundFile::RETRY_ATTEMPT)
                  ->nullable()
                  ->default(0);
        
            $table->integer(RefundFile::AMOUNT)
                  ->nullable()
                  ->default(null);

            $table->text(RefundFile::COMMENT)
                  ->nullable()
                  ->default(null);

            $table->integer(RefundFile::PROCESSED_AT)
                  ->nullable()
                  ->default(null);

            $table->integer(RefundFile::CREATED_AT);
            $table->integer(RefundFile::UPDATED_AT);

            $table->foreign(RefundFile::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
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
        Schema::table(Table::REFUND_FILE, function($table)
        {
            $table->dropForeign(Table::REFUND_FILE.'_'.RefundFile::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::REFUND_FILE);
    }
}