<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Admin\Entity as Admin;
use RZP\Models\Workflow\Action\Comment\Entity as Comment;
use RZP\Models\Workflow\Action\Entity as Action;

class CreateWorkflowActionComment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ACTION_COMMENT, function (BluePrint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Comment::ID, Comment::ID_LENGTH)
                  ->primary();

            $table->char(Comment::ACTION_ID, Comment::ID_LENGTH);
            $table->char(Comment::ADMIN_ID, Comment::ID_LENGTH);

            $table->text(Comment::COMMENT);

            $table->foreign(Comment::ACTION_ID)
                  ->references(Action::ID)
                  ->on(Table::WORKFLOW_ACTION)
                  ->on_delete('restrict');

            $table->foreign(Comment::ADMIN_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN)
                  ->on_delete('restrict');

            $table->integer(Comment::CREATED_AT);
            $table->integer(Comment::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ACTION_COMMENT, function($table)
        {
            $table->dropForeign(Table::ACTION_COMMENT . '_' . Comment::ADMIN_ID . '_foreign');

            $table->dropForeign(Table::ACTION_COMMENT . '_' . Comment::ACTION_ID . '_foreign');
        });

        Schema::drop(Table::ACTION_COMMENT);
    }
}
