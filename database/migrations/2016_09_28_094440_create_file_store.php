<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\FileStore\Entity as FileStore;
use RZP\Models\Transaction\Entity as Transaction;
use RZP\Models\FundTransfer\Batch\Entity as BatchFundTransfer;

class CreateFileStore extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FILE_STORE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(FileStore::ID, FileStore::ID_LENGTH)
                  ->primary();

            $table->char(FileStore::MERCHANT_ID, FileStore::ID_LENGTH);

            $table->string(FileStore::TYPE);

            $table->string(FileStore::ENTITY_ID, FileStore::ID_LENGTH)->nullable();

            $table->string(FileStore::ENTITY_TYPE)->nullable();

            $table->text(FileStore::COMMENTS)->nullable();

            $table->string(FileStore::EXTENSION)->nullable();

            $table->string(FileStore::MIME)->nullable();

            $table->bigInteger(FileStore::SIZE);

            $table->string(FileStore::NAME);

            $table->string(FileStore::STORE);

            $table->string(FileStore::LOCATION);

            $table->string(FileStore::BUCKET)->nullable();

            $table->string(FileStore::REGION)->nullable();

            $table->string(FileStore::PERMISSION)->nullable();

            $table->string(FileStore::ENCRYPTION_METHOD)->nullable();

            $table->string(FileStore::PASSWORD)->nullable();

            $table->string(FileStore::METADATA)->nullable();

            $table->integer(FileStore::CREATED_AT);

            $table->integer(FileStore::UPDATED_AT);

            $table->integer(FileStore::DELETED_AT)->nullable();

            $table->index(FileStore::ENTITY_ID);
            $table->index(FileStore::ENTITY_TYPE);
            $table->index(FileStore::TYPE);
            $table->index(FileStore::CREATED_AT);
            $table->index(FileStore::DELETED_AT);

            $table->foreign(FileStore::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });

        Schema::table(Table::BATCH_FUND_TRANSFER, function($table)
        {
            $table->char(BatchFundTransfer::TXT_FILE_ID, FileStore::ID_LENGTH)
                  ->nullable();

            $table->char(BatchFundTransfer::EXCEL_FILE_ID, FileStore::ID_LENGTH)
                  ->nullable();

            $table->foreign(BatchFundTransfer::TXT_FILE_ID)
                  ->references(FileStore::ID)
                  ->on(Table::FILE_STORE)
                  ->on_delete('restrict');

            $table->foreign(BatchFundTransfer::EXCEL_FILE_ID)
                  ->references(FileStore::ID)
                  ->on(Table::FILE_STORE)
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
        Schema::table(Table::BATCH_FUND_TRANSFER, function($table)
        {
            $table->dropForeign(
                  Table::BATCH_FUND_TRANSFER.'_'.BatchFundTransfer::EXCEL_FILE_ID.'_foreign');

            $table->dropForeign(
                  Table::BATCH_FUND_TRANSFER.'_'.BatchFundTransfer::TXT_FILE_ID.'_foreign');
        });

        Schema::table(Table::FILE_STORE, function($table)
        {
            $table->dropForeign(
                Table::FILE_STORE . '_' . Transaction::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::FILE_STORE);
    }
}
