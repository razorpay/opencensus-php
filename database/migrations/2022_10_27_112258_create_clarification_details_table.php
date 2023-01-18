<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\ClarificationDetail\Entity;
use RZP\Models\ClarificationDetail\Constants;

class CreateClarificationDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CLARIFICATION_DETAIL, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, 14)->primary();

            $table->char(Entity::MERCHANT_ID, 14);

            $table->string(Entity::STATUS, 30)->nullable();

            $table->json(Entity::COMMENT_DATA)->nullable();

            $table->string(Entity::MESSAGE_FROM, 30)->nullable();

            $table->string(Entity::GROUP_NAME, 255)->nullable();

            $table->json(Entity::FIELD_DETAILS)->nullable();

            $table->json(Entity::METADATA)->nullable();

            $table->char(Entity::AUDIT_ID, 14);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::CLARIFICATION_DETAIL);
    }
}
