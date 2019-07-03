<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Workflow\PayoutAmountRules\Entity;

class CreateWorkflowPayoutAmountRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WORKFLOW_PAYOUT_AMOUNT_RULES, function (Blueprint $table)
        {
            $table->increments(Entity::ID);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::CONDITION, 255)
                  ->nullable();

            $table->unsignedBigInteger(Entity::MIN_AMOUNT)
                  ->nullable();

            $table->unsignedBigInteger(Entity::MAX_AMOUNT)
                  ->nullable();

            $table->char(Entity::WORKFLOW_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::WORKFLOW_ID);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::DELETED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::WORKFLOW_PAYOUT_AMOUNT_RULES);
    }
}
