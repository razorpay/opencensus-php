<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Role\Entity as Role;

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
