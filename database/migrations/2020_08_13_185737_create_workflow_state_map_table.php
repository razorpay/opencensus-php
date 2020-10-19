<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Workflow\Service\StateMap\Entity as WorkflowStateMap;

class CreateWorkflowStateMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WORKFLOW_STATE_MAP, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(WorkflowStateMap::ID, WorkflowStateMap::ID_LENGTH)
                ->primary();

            $table->char(WorkflowStateMap::WORKFLOW_ID, WorkflowStateMap::ID_LENGTH);

            $table->string(WorkflowStateMap::ACTOR_TYPE_KEY, 255);

            $table->string(WorkflowStateMap::ACTOR_TYPE_VALUE, 255);

            $table->char(WorkflowStateMap::STATE_ID, WorkflowStateMap::ID_LENGTH);

            $table->char(WorkflowStateMap::STATE_NAME, 255);

            $table->char(WorkflowStateMap::STATUS, 255);

            $table->char(WorkflowStateMap::GROUP_NAME, 255);

            $table->char(WorkflowStateMap::TYPE, 255);

            $table->char(WorkflowStateMap::ORG_ID, WorkflowStateMap::ID_LENGTH);

            $table->char(WorkflowStateMap::MERCHANT_ID, WorkflowStateMap::ID_LENGTH);

            $table->integer(WorkflowStateMap::CREATED_AT);

            $table->integer(WorkflowStateMap::UPDATED_AT);

            $table->index(WorkflowStateMap::STATE_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::WORKFLOW_STATE_MAP);
    }
}
