<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

class CreateGroupMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GROUP_MAP, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('group_id', 14);

            $table->char('entity_id', 14);

            $table->string('entity_type', 10); // admin or group

            $table->unique(['group_id', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::GROUP_MAP);
    }
}
