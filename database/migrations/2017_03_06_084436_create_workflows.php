<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Workflow\Entity as Workflow;
use RZP\Models\Admin\Org\Entity as Org;

class CreateWorkflows extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WORKFLOW, function (BluePrint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Workflow::ID, Workflow::ID_LENGTH)
                  ->primary();

            $table->char(Workflow::ORG_ID, Workflow::ID_LENGTH);

            $table->foreign(Workflow::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG)
                  ->on_delete('restrict');

            $table->char(Workflow::NAME, 255);

            $table->integer(Workflow::CREATED_AT);
            $table->integer(Workflow::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::WORKFLOW, function($table)
        {
            $table->dropForeign(Table::WORKFLOW . '_' . Workflow::ORG_ID . '_foreign');

            $table->dropForeign(Table::WORKFLOW . '_' . Workflow::ORG_ID . '_foreign');
        });

        //
        Schema::drop(Table::GROUP);
    }
}
