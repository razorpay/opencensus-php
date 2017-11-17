<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\State\Entity as State;
use RZP\Models\Admin\Admin\Entity as Admin;
use RZP\Models\Workflow\Action\Entity as Action;

class CreateActionState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::STATE, function (BluePrint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(State::ID, State::ID_LENGTH)
                  ->primary();

            $table->char(State::ACTION_ID, Action::ID_LENGTH)->nullable();

            $table->char(State::ENTITY_ID, State::ID_LENGTH)->nullable();

            $table->char(State::ENTITY_TYPE, 100)->nullable();

            $table->char(State::ADMIN_ID, Admin::ID_LENGTH)
                  ->nullable();

            $table->char(State::MERCHANT_ID, Merchant::ID_LENGTH)
                  ->nullable();

            $table->char(State::NAME, 255);

            $table->integer(State::CREATED_AT);

            $table->integer(State::UPDATED_AT);

            $table->foreign(State::ACTION_ID)
                  ->references(Action::ID)
                  ->on(Table::WORKFLOW_ACTION)
                  ->on_delete('restrict');

            $table->foreign(State::ADMIN_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN)
                  ->on_delete('restrict');

            $table->foreign(State::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index(State::ENTITY_ID);
            $table->index(State::ENTITY_TYPE);
            $table->index(State::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::STATE, function($table)
        {
            $table->dropForeign(Table::STATE . '_' . State::ACTION_ID . '_foreign');

            $table->dropForeign(Table::STATE . '_' . State::ADMIN_ID . '_foreign');
        });

        Schema::drop(Table::STATE);
    }
}
