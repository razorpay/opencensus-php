<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Workflow\Entity as Workflow;


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

            $table->char(Workflow::MERCHANT_ID, Workflow::ID_LENGTH)
                  ->nullable();

            $table->foreign(Workflow::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG)
                  ->on_delete('restrict');

            $table->foreign(Workflow::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->char(Workflow::NAME, 255);

            $table->integer(Workflow::DELETED_AT)
                  ->nullable();

            $table->integer(Workflow::CREATED_AT);

            $table->integer(Workflow::UPDATED_AT);

            $table->index(Workflow::CREATED_AT);

            $table->index(Workflow::MERCHANT_ID);
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

            $table->dropForeign(Table::WORKFLOW . '_' . Workflow::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::WORKFLOW);
    }
}
