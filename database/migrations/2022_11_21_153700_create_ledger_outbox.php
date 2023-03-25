<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;

use RZP\Models\LedgerOutbox\Entity as Entity;

class CreateLedgerOutbox extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create(Table::LEDGER_OUTBOX, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->string(Entity::PAYLOAD_NAME, 100)->nullable(false);

            $table->text(Entity::PAYLOAD_SERIALIZED)->nullable(false);

            $table->boolean(Entity::IS_ENCRYPTED)->default(false);

            $table->integer(Entity::PRIORITY)->nullable();

            $table->boolean(Entity::IS_DELETED)->default(false);

            $table->integer(Entity::RETRY_COUNT)->default(0);

            $table->integer(Entity::CREATED_AT)->nullable(false);

            $table->integer(Entity::UPDATED_AT)->nullable(false);

            $table->integer(Entity::DELETED_AT)->nullable();

            $table->index(Entity::PAYLOAD_NAME);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::LEDGER_OUTBOX);
    }

}
