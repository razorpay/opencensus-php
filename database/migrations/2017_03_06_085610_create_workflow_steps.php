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

            $table->tinyInteger(Step::LEVEL)
                  ->default(0);

            $table->foreign(Step::ROLE_ID)
                  ->references(Role::ID)
                  ->on(Table::ROLE)
                  ->on_delete('restrict');

            $table->foreign(Step::WORKFLOW_ID)
                  ->references(Workflow::ID)
                  ->on(Table::WORKFLOW)
                  ->on_delete('restrict');

            $table->integer(Step::CREATED_AT);

            $table->integer(Step::UPDATED_AT);

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
