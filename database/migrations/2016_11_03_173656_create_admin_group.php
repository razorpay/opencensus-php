<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

class CreateAdminGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADMIN_GROUP, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('group_id', 14);

            $table->char('entity_id', 14);

            $table->string('entity_type'); // admin or group
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ADMIN_GROUP);
    }
}
