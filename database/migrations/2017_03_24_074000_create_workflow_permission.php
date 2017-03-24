<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Workflow\Entity as Workflow;
use RZP\Models\Admin\Permission\Entity as Permission;

class CreateWorkflowPermission extends Migration
{
    const WORKFLOW_ID   = 'workflow_id';
    const PERMISSION_ID = 'permission_id';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WORKFLOW_PERMISSION, function(Blueprint $table)
        {
            //
            $table->engine = 'InnoDB';

            $table->char(self::WORKFLOW_ID, Merchant::ID_LENGTH);

            $table->char(self::PERMISSION_ID, Terminal::ID_LENGTH);

            $table->foreign(self::WORKFLOW_ID)
                    ->references(Merchant::ID)
                    ->on(Table::MERCHANT)
                    ->onDelete('cascade');

            $table->foreign(self::PERMISSION_ID)
                    ->references(Permission::ID)
                    ->on(Table::PERMISSION)
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table(Table::WORKFLOW_PERMISSION, function($table)
        {
            $table->dropForeign(
                Table::WORKFLOW_PERMISSION . '_' . self::WORKFLOW_ID . '_foreign');

            $table->dropForeign(
                Table::WORKFLOW_PERMISSION . '_' . self::PERMISSION_ID . '_foreign');
        });

        Schema::drop(Table::WORKFLOW_PERMISSION);
    }
}
