<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Admin\Entity as Admin;
use RZP\Models\Workflow\Step\Entity as Step;
use RZP\Models\Workflow\Action\Entity as Action;
use RZP\Models\Workflow\Action\Checker\Entity as Checker;

class CreateActionChecker extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ACTION_CHECKER, function (BluePrint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Checker::ID, Checker::ID_LENGTH)
                  ->primary();

            $table->char(Checker::ADMIN_ID, Checker::ID_LENGTH)
                  ->nullable();

            $table->char(Checker::CHECKER_ID, Checker::ID_LENGTH)
                  ->nullable();

            $table->string(Checker::CHECKER_TYPE, 255)
                  ->nullable();

            $table->char(Checker::ACTION_ID, Checker::ID_LENGTH);

            $table->char(Checker::STEP_ID, Checker::ID_LENGTH);

            $table->tinyInteger(Checker::APPROVED)
                  ->nullable();

            $table->string(Checker::USER_COMMENT, 255)
                  ->nullable();

            $table->foreign(Checker::STEP_ID)
                  ->references(Step::ID)
                  ->on(Table::WORKFLOW_STEP)
                  ->on_delete('restrict');

            $table->foreign(Checker::ACTION_ID)
                  ->references(Action::ID)
                  ->on(Table::WORKFLOW_ACTION)
                  ->on_delete('restrict');

            $table->foreign(Checker::ADMIN_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN)
                  ->on_delete('restrict');

            $table->integer(Checker::CREATED_AT);

            $table->integer(Checker::UPDATED_AT);

            $table->index(Checker::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ACTION_CHECKER, function($table)
        {
            $table->dropForeign(Table::ACTION_CHECKER . '_' . Checker::ACTION_ID . '_foreign');

            $table->dropForeign(Table::ACTION_CHECKER . '_' . Checker::ADMIN_ID . '_foreign');

            $table->dropForeign(Table::ACTION_CHECKER . '_' . Checker::STEP_ID . '_foreign');
        });

        Schema::drop(Table::ACTION_CHECKER);
    }
}
