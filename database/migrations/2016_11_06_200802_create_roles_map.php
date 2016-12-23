<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

class CreateRolesMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ROLE_MAP, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('role_id', 14);

            $table->char('entity_id', 14);

            $table->string('entity_type'); // groups or admins

            $table->unique(['role_id', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ROLE_MAP);
    }
}
