<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Workflow\Service\StateMap\Entity as WorkflowStateMap;
use RZP\Models\Payout\DataMigration\WorkflowStateMap as MigrationWorkflowStateMap;

class CreatePsWorkflowStateMap extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_workflow_state_map', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(WorkflowStateMap::ID, WorkflowStateMap::ID_LENGTH)
                  ->primary();

            $table->char(WorkflowStateMap::WORKFLOW_ID, WorkflowStateMap::ID_LENGTH);

            $table->char(MigrationWorkflowStateMap::ACTOR_ROLE, 255);

            $table->char(WorkflowStateMap::STATE_ID, WorkflowStateMap::ID_LENGTH);

            $table->string(MigrationWorkflowStateMap::STATE_STATUS, 255);

            $table->string(WorkflowStateMap::GROUP_NAME, 255);

            $table->char(WorkflowStateMap::MERCHANT_ID, WorkflowStateMap::ID_LENGTH);

            $table->char(WorkflowStateMap::TYPE, WorkflowStateMap::ID_LENGTH);

            $table->integer(WorkflowStateMap::CREATED_AT);

            $table->integer(WorkflowStateMap::UPDATED_AT);

            $table->integer(MigrationWorkflowStateMap::COUNT_OF_APPROVALS_NEEDED);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_workflow_state_map');
    }
}
