<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\FileHandler\Entity as FileHandler;

class CreateFileHandler extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FILE_HANDLER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(FileHandler::ID, FileHandler::ID_LENGTH)
                  ->primary();

            $table->char(FileHandler::MERCHANT_ID, FileHandler::ID_LENGTH);

            $table->string(FileHandler::DOCUMENT_TYPE);

            $table->string(FileHandler::ENTITY_ID, FileHandler::ID_LENGTH)->nullable();

            $table->string(FileHandler::ENTITY_TYPE)->nullable();

            $table->string(FileHandler::COMMENTS)->nullable();

            $table->string(FileHandler::FORMAT);

            $table->bigInteger(FileHandler::SIZE);

            $table->string(FileHandler::NAME);

            $table->string(FileHandler::SERVICE);

            $table->string(FileHandler::LOCATION);

            $table->string(FileHandler::BUCKET)->nullable();

            $table->string(FileHandler::PERMISSION)->nullable();

            $table->string(FileHandler::ENCRYPTION_METHOD);

            $table->string(FileHandler::PASSWORD)->nullable();

            $table->string(FileHandler::METADATA)->nullable();

            $table->string(FileHandler::CREATED_AT);

            $table->string(FileHandler::UPDATED_AT);

            $table->string(FileHandler::DELETED_AT)->nullable();

            $table->index([FileHandler::ENTITY_ID);

            $table->index(FileHandler::ENTITY_TYPE]);

            $table->index(FileHandler::DOCUMENT_TYPE);

            $table->index(FileHandler::CREATED_AT);

            $table->index(FileHandler::DELETED_AT);

            $table->foreign(FILE_HANDLER::MERCHANT_ID)
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
        Schema::table(Table::FILE_HANDLER, function($table)
        {
            $table->dropForeign(
                TABLE::FILE_HANDLER.'_'.Transaction::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::FILE_HANDLER);
    }
}
