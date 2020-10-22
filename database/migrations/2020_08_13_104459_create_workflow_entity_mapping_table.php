<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Workflow\Service\EntityMap\Entity as WorkflowEntityMap;

class CreateWorkflowEntityMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WORKFLOW_ENTITY_MAP, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(WorkflowEntityMap::ID, WorkflowEntityMap::ID_LENGTH)
                  ->primary();

            $table->char(WorkflowEntityMap::WORKFLOW_ID, WorkflowEntityMap::ID_LENGTH);

            $table->char(WorkflowEntityMap::CONFIG_ID, WorkflowEntityMap::ID_LENGTH);

            $table->char(WorkflowEntityMap::ENTITY_ID, WorkflowEntityMap::ID_LENGTH);

            $table->string(WorkflowEntityMap::ENTITY_TYPE, 255);

            $table->char(WorkflowEntityMap::MERCHANT_ID, WorkflowEntityMap::ID_LENGTH);

            $table->char(WorkflowEntityMap::ORG_ID, WorkflowEntityMap::ID_LENGTH)->nullable();

            $table->integer(WorkflowEntityMap::CREATED_AT);

            $table->integer(WorkflowEntityMap::UPDATED_AT);

            $table->index(WorkflowEntityMap::WORKFLOW_ID);

            $table->index([
                    WorkflowEntityMap::ENTITY_TYPE,
                    WorkflowEntityMap::ENTITY_ID]
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::WORKFLOW_ENTITY_MAP);
    }
}
