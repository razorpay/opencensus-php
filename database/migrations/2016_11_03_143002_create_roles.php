<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Role\Entity as Role;
use RZP\Models\Admin\Org\Entity as Org;

class CreateRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ROLE, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Role::ID, 14)
                  ->primary();

            $table->string(Role::NAME, 250);
            $table->string(Role::DESCRIPTION, 250);

            $table->char(Role::ORG_ID, 14);

            $table->integer(Role::CREATED_AT);
            $table->integer(Role::UPDATED_AT);

            $table->unique([Role::NAME, Role::ORG_ID]);

            $table->foreign(Role::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ROLE);
    }
}
