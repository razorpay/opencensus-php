<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
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
        //
        Schema::drop(Table::GROUP);
    }
}
