<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
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

            $table->increments(FileHandler::ID);

            $table->string(FileHandler::FORMAT);

            $table->bigInteger(FileHandler::SIZE);

            $table->string(FileHandler::ENCRYPTION_METHOD);

            $table->string(FileHandler::LOCATION);

            $table->string(FileHandler::SERVICE);

            $table->string(FileHandler::NAME);

            $table->string(FileHandler::PASSWORD);

            $table->string(FileHandler::ENTITY_NAME);

            $table->string(FileHandler::ENTITY_ID);

            $table->string(FileHandler::MERCHANT_ID);

            $table->string(FileHandler::PERMISSION);

            $table->string(FileHandler::COMMENTS);

            $table->string(FileHandler::METADATA);

            $table->string(FileHandler::DOCUMENT_TYPE);

            $table->string(FileHandler::CREATED_AT);

            $table->string(FileHandler::UPDATED_AT);

            $table->string(FileHandler::DELETED_AT);

            $table->index([FileHandler::ENTITY_ID, FileHandler::ENTITY_NAME]);

            $table->index(FileHandler::MERCHANT_ID);

            $table->index(FileHandler::DOCUMENT_TYPE);

            $table->index(FileHandler::CREATED_AT);

            $table->index(FileHandler::UPDATED_AT);

            $table->index(FileHandler::DELETED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::FILE_HANDLER);
    }
}
