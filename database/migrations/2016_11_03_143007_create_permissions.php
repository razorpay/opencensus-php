<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Permission\Entity as Permission;

class CreatePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PERMISSION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Permission::ID, 14)
                  ->primary();

            $table->string(Permission::NAME, 250);
            $table->string(Permission::DESCRIPTION, 250);

            $table->integer(Permission::CREATED_AT);
            $table->integer(Permission::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::PERMISSION);
    }
}
