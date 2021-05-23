<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Admin\Role\Entity as Role;
use RZP\Models\Workflow\Entity as Workflow;
use RZP\Models\Admin\Admin\Entity as Admin;
use RZP\Models\Workflow\Action\Entity as Action;
use RZP\Models\Admin\Permission\Entity as Permission;


class CreateWorkflowAction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WORKFLOW_ACTION, function (BluePrint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Action::ID, Action::ID_LENGTH)
                  ->primary();

            $table->char(Action::ENTITY_ID, Action::ID_LENGTH)
                  ->nullable();

            $table->char(Action::ENTITY_NAME, 255)
                  ->nullable();

            $table->string(Action::TITLE, 255)
                  ->nullable();

            $table->text(Action::DESCRIPTION)
                  ->nullable();

            $table->char(Action::WORKFLOW_ID, Action::ID_LENGTH);

            $table->char(Action::PERMISSION_ID, Action::ID_LENGTH)
                  ->nullable();

            $table->char(Action::OWNER_ID, Action::ID_LENGTH)
                  ->nullable();

            $table->char(Action::MAKER_ID, Action::ID_LENGTH);

            $table->char(Action::MAKER_TYPE, Action::ID_LENGTH);

            $table->char(Action::ORG_ID, Action::ID_LENGTH);

            $table->char(Action::STATE_CHANGER_ID, Action::ID_LENGTH)
                  ->nullable();

            $table->char(Action::STATE_CHANGER_TYPE, 255)
                  ->nullable();

            $table->char(Action::STATE_CHANGER_ROLE_ID, Action::ID_LENGTH)
                  ->nullable();

            $table->boolean(Action::APPROVED)
                  ->default(0);

            $table->tinyInteger(Action::CURRENT_LEVEL)
                  ->nullable();

            $table->char(Action::STATE, 25);

            $table->integer(Action::ASSIGNED_AT)->nullable();

            $table->foreign(Action::WORKFLOW_ID)
                  ->references(Workflow::ID)
                  ->on(Table::WORKFLOW)
                  ->on_delete('restrict');

            $table->foreign(Action::PERMISSION_ID)
                  ->references(Permission::ID)
                  ->on(Table::PERMISSION)
                  ->on_delete('restrict');

            $table->foreign(Action::STATE_CHANGER_ROLE_ID)
                  ->references(Role::ID)
                  ->on(Table::ROLE)
                  ->on_delete('restrict');

            $table->foreign(Action::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG)
                  ->on_delete('restrict');

            $table->integer(Action::CREATED_AT);

            $table->integer(Action::UPDATED_AT);

            $table->index([Action::ENTITY_ID, Action::ENTITY_NAME]);

            $table->index([Action::MAKER_ID, Action::MAKER_TYPE]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::WORKFLOW_ACTION, function($table)
        {
            $table->dropForeign(Table::WORKFLOW_ACTION . '_' . Action::WORKFLOW_ID . '_foreign');

            $table->dropForeign(Table::WORKFLOW_ACTION . '_' . Action::PERMISSION_ID . '_foreign');

            $table->dropForeign(Table::WORKFLOW_ACTION . '_' . Action::STATE_CHANGER_ID . '_foreign');

            $table->dropForeign(Table::WORKFLOW_ACTION . '_' . Action::STATE_CHANGER_ROLE_ID . '_foreign');

            $table->dropForeign(Table::WORKFLOW_ACTION . '_' . Action::ORG_ID . '_foreign');
        });

        Schema::drop(Table::WORKFLOW_ACTION);
    }
}
