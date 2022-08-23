<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Role\Entity as Role;
use RZP\Models\Workflow\Entity as Workflow;
use RZP\Models\Workflow\Step\Entity as Step;

class CreateWorkflowSteps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WORKFLOW_STEP, function (BluePrint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Step::ID, Step::ID_LENGTH)
                  ->primary();

            $table->char(Step::ROLE_ID, Step::ID_LENGTH);

            $table->char(Step::WORKFLOW_ID, Step::ID_LENGTH);

            $table->tinyInteger(Step::REVIEWER_COUNT)
                  ->default(0);

            $table->string(Step::OP_TYPE, 15)
                  ->default(Step::OP_TYPE_AND);

            $table->tinyInteger(Step::LEVEL)
                  ->default(0);

            $table->integer(Step::CREATED_AT);

            $table->integer(Step::UPDATED_AT);

            $table->integer(Step::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->index([Step::CREATED_AT, Step::DELETED_AT]);
            $table->index(Step::LEVEL);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::WORKFLOW_STEP, function($table)
        {
            $table->dropForeign(Table::WORKFLOW_STEP . '_' . Step::ROLE_ID . '_foreign');

            $table->dropForeign(Table::WORKFLOW_STEP . '_' . Step::WORKFLOW_ID . '_foreign');
        });

        Schema::drop(Table::WORKFLOW_STEP);
    }
}
