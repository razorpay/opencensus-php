<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Group\Entity as Group;
use RZP\Models\Admin\Org\Entity as Org;

class CreateGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GROUP, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Group::ID, Group::ID_LENGTH)
                  ->primary();

            $table->char(Group::ORG_ID, Group::ID_LENGTH);

            $table->string(Group::NAME, 250);
            $table->string(Group::DESCRIPTION, 250);

            $table->integer(Group::CREATED_AT);
            $table->integer(Group::UPDATED_AT);

            $table->integer(Group::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->unique([Group::NAME, Group::ORG_ID]);

            $table->foreign(Group::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG);
            $table->index(Group::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::GROUP);
    }
}
