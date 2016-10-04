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

            $table->char(FileHandler::ID, FileHandler::ID_LENGTH)
                  ->primary();

            $table->string(FileHandler::FORMAT);

            $table->bigInteger(FileHandler::SIZE);

            $table->string(FileHandler::ENCRYPTION_METHOD);

            $table->string(FileHandler::LOCATION);

            $table->string(FileHandler::SERVICE);

            $table->string(FileHandler::BUCKET)->nullable();

            $table->string(FileHandler::NAME);

            $table->string(FileHandler::PASSWORD)->nullable();

            $table->string(FileHandler::ENTITY_TYPE)->nullable();

            $table->string(FileHandler::ENTITY_ID)->nullable();

            $table->string(FileHandler::MERCHANT_ID)->nullable();

            $table->string(FileHandler::PERMISSION)->nullable();

            $table->string(FileHandler::COMMENTS)->nullable();

            $table->string(FileHandler::METADATA)->nullable();

            $table->string(FileHandler::DOCUMENT_TYPE);

            $table->string(FileHandler::CREATED_AT);

            $table->string(FileHandler::UPDATED_AT);

            $table->string(FileHandler::DELETED_AT)->nullable();

            $table->index([FileHandler::ENTITY_ID, FileHandler::ENTITY_TYPE]);

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
