<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Group\Entity as Group;

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

            $table->char(Group::ID, 14)
                  ->primary();

            $table->string(Group::NAME, 250);
            $table->string(Group::DESCRIPTION, 250);

            $table->char(Group::ORG_ID, 14);

            $table->integer(Group::CREATED_AT);
            $table->integer(Group::UPDATED_AT);
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
