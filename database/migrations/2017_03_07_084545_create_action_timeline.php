<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Admin\Entity as Admin;
use RZP\Models\Workflow\Action\Entity as Action;
use RZP\Models\Workflow\Action\Timeline\Entity as Timeline;

class CreateActionTimeline extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ACTION_TIMELINE, function (BluePrint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Timeline::ID, Timeline::ID_LENGTH)
                  ->primary();

            $table->char(Timeline::ACTION_ID, Timeline::ID_LENGTH);
            $table->char(Timeline::ADMIN_ID, Timeline::ID_LENGTH);

            $table->char(Timeline::STATE, 255);

            $table->foreign(Timeline::ACTION_ID)
                  ->references(Action::ID)
                  ->on(Table::WORKFLOW_ACTION)
                  ->on_delete('restrict');

            $table->foreign(Timeline::ADMIN_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN)
                  ->on_delete('restrict');

            $table->integer(Timeline::CREATED_AT);
            $table->integer(Timeline::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ACTION_TIMELINE, function($table)
        {
            $table->dropForeign(Table::ACTION_TIMELINE . '_' . Timeline::ACTION_ID . '_foreign');

            $table->dropForeign(Table::ACTION_TIMELINE . '_' . Timeline::ADMIN_ID . '_foreign');
        });

        Schema::drop(Table::ACTION_TIMELINE);
    }
}
