<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Dispute\Evidence\Entity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDisputeEvidenceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::DISPUTE_EVIDENCE, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::DISPUTE_ID, RZP\Models\Dispute\Entity::ID_LENGTH);

            $table->mediumText(Entity::SUMMARY);

            $table->bigInteger(Entity::AMOUNT);

            $table->string(Entity::CURRENCY, Entity::CURRENCY_LENGTH);

            $table->string(Entity::REJECTION_REASON)->nullable()->default(null);

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
        Schema::dropIfExists(Table::DISPUTE_EVIDENCE);
    }
}
