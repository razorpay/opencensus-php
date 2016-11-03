<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Roles\Entity as Roles;

class CreateRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ROLE, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Roles::ID, 14)
                  ->primary();

            $table->string(Roles::NAME, 250);
            $table->string(Roles::DESCRIPTION, 250);

            $table->char(Roles::ORGANIZATION_ID, 14);

            $table->integer(Roles::CREATED_AT);
            $table->integer(Roles::UPDATED_AT);
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
