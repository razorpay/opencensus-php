<?php


use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Dispute\Evidence\Document\Entity;

class CreateDisputeDocumentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::DISPUTE_EVIDENCE_DOCUMENT, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::DISPUTE_ID, RZP\Models\Dispute\Entity::ID_LENGTH);

            $table->string(Entity::TYPE, Entity::TYPE_LENGTH);

            $table->string(Entity::CUSTOM_TYPE, Entity::CUSTOM_TYPE_LENGTH)->nullable()->default(null);

            $table->char(Entity::DOCUMENT_ID, Entity::ID_LENGTH); // sign will be stripped before storing

            $table->string(Entity::SOURCE, Entity::SOURCE_LENGTH);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)->nullable()->default(null);

            //indexes
            $table->index(Entity::DISPUTE_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::DISPUTE_EVIDENCE_DOCUMENT);
    }
}
