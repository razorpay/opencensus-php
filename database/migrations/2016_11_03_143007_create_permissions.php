<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Permissions\Entity as Permissions;

class CreatePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PERMISSION, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Permissions::ID, 14)
                  ->primary();

            $table->string(Permissions::NAME, 250);
            $table->string(Permissions::DESCRIPTION, 250);

            $table->integer(Permissions::CREATED_AT);
            $table->integer(Permissions::UPDATED_AT);
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
