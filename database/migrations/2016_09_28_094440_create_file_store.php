<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\FileStore\Entity as FileStore;

class CreateFileStore extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FILESTORE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(FileStore::ID, FileStore::ID_LENGTH)
                  ->primary();

            $table->char(FileStore::MERCHANT_ID, FileStore::ID_LENGTH);

            $table->string(FileStore::TYPE);

            $table->string(FileStore::ENTITY_ID, FileStore::ID_LENGTH)->nullable();

            $table->string(FileStore::ENTITY_TYPE)->nullable();

            $table->string(FileStore::COMMENTS)->nullable();

            $table->string(FileStore::FORMAT);

            $table->bigInteger(FileStore::SIZE);

            $table->string(FileStore::NAME);

            $table->string(FileStore::SERVICE);

            $table->string(FileStore::LOCATION);

            $table->string(FileStore::BUCKET)->nullable();

            $table->string(FileStore::PERMISSION)->nullable();

            $table->string(FileStore::ENCRYPTION_METHOD);

            $table->string(FileStore::PASSWORD)->nullable();

            $table->string(FileStore::METADATA)->nullable();

            $table->string(FileStore::CREATED_AT);

            $table->string(FileStore::UPDATED_AT);

            $table->string(FileStore::DELETED_AT)->nullable();

            $table->index(FileStore::ENTITY_ID);

            $table->index(FileStore::ENTITY_TYPE);

            $table->index(FileStore::TYPE);

            $table->index(FileStore::CREATED_AT);

            $table->index(FileStore::DELETED_AT);

            $table->foreign(FILESTORE::MERCHANT_ID)
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
        Schema::table(Table::FILESTORE, function($table)
        {
            $table->dropForeign(
                TABLE::FILESTORE . '_' . Transaction::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::FILESTORE);
    }
}
