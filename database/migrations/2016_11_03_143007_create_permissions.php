<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Illuminate\Support\Facades\Schema;
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

            $table->char(Permission::ID, Permission::ID_LENGTH)
                  ->primary();

            $table->string(Permission::NAME, 250);
            $table->string(Permission::CATEGORY, 250)
                  ->nullable();

            $table->string(Permission::DESCRIPTION, 250)
                  ->nullable();

            $table->boolean(Permission::ASSIGNABLE)
                  ->default(0);

            $table->integer(Permission::CREATED_AT);
            $table->integer(Permission::UPDATED_AT);

            $table->unique([Permission::NAME, Permission::CATEGORY]);
            $table->index(Permission::CREATED_AT);
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
