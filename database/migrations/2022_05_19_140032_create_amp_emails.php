<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\AMPEmail\Entity as AMPEmailEntity;

class CreateAMPEmails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::AMP_EMAIL, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(AMPEmailEntity::ID, AMPEmailEntity::ID_LENGTH)
                  ->primary();

            $table->char(AMPEmailEntity::ENTITY_ID, AMPEmailEntity::ID_LENGTH);

            $table->json(AMPEmailEntity::METADATA)
                  ->nullable();

            $table->string(AMPEmailEntity::ENTITY_TYPE, 30);

            $table->string(AMPEmailEntity::VENDOR, 30);

            $table->string(AMPEmailEntity::STATUS, 30);

            $table->string(AMPEmailEntity::TEMPLATE, 30);

            $table->integer(AMPEmailEntity::CREATED_AT);

            $table->integer(AMPEmailEntity::UPDATED_AT);


            //index
            $table->index(AMPEmailEntity::ENTITY_ID,AMPEmailEntity::TEMPLATE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::AMP_EMAIL);
    }
}
