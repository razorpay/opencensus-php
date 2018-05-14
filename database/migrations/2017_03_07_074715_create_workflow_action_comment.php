<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Comment\Entity as Comment;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Admin\Admin\Entity as Admin;
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
        Schema::create(Table::COMMENT, function (BluePrint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Comment::ID, Comment::ID_LENGTH)
                  ->primary();

            $table->char(Comment::ACTION_ID, Action::ID_LENGTH)->nullable();

            $table->char(Comment::ENTITY_ID, Comment::ID_LENGTH)->nullable();

            $table->char(Comment::ENTITY_TYPE, 100)->nullable();

            $table->char(Comment::ADMIN_ID, Admin::ID_LENGTH)
                  ->nullable();

            $table->char(Comment::MERCHANT_ID, Merchant::ID_LENGTH)
                  ->nullable();

            $table->text(Comment::COMMENT);

            $table->integer(Comment::CREATED_AT);

            $table->integer(Comment::UPDATED_AT);

            $table->foreign(Comment::ACTION_ID)
                  ->references(Action::ID)
                  ->on(Table::WORKFLOW_ACTION)
                  ->on_delete('restrict');

            $table->foreign(Comment::ADMIN_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN)
                  ->on_delete('restrict');

            $table->foreign(Comment::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index(Comment::ENTITY_TYPE);
            $table->index(Comment::CREATED_AT);
            $table->index([Comment::ENTITY_ID, Comment::ENTITY_TYPE]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::COMMENT, function($table)
        {
            $table->dropForeign(Table::COMMENT . '_' . Comment::ADMIN_ID . '_foreign');

            $table->dropForeign(Table::COMMENT . '_' . Comment::ACTION_ID . '_foreign');
        });

        Schema::drop(Table::COMMENT);
    }
}
