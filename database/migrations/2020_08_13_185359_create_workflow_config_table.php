<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Workflow\Service\Config\Entity as WorkflowConfig;

class CreateWorkflowConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WORKFLOW_CONFIG, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(WorkflowConfig::ID, WorkflowConfig::ID_LENGTH)
                ->primary();

            $table->char(WorkflowConfig::CONFIG_ID, WorkflowConfig::ID_LENGTH);

            $table->string(WorkflowConfig::CONFIG_TYPE, 255);

            $table->boolean(WorkflowConfig::ENABLED)->default(false);

            $table->char(WorkflowConfig::MERCHANT_ID, WorkflowConfig::ID_LENGTH);

            $table->char(WorkflowConfig::ORG_ID, WorkflowConfig::ID_LENGTH)->nullable();

            $table->integer(WorkflowConfig::CREATED_AT);

            $table->integer(WorkflowConfig::UPDATED_AT);

            $table->index(WorkflowConfig::CONFIG_ID);

            $table->index([
                WorkflowConfig::CONFIG_TYPE,
                WorkflowConfig::MERCHANT_ID
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::WORKFLOW_CONFIG);
    }
}
