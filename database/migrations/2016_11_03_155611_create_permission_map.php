<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

class CreatePermissionMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PERMISSION_MAP, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('permission_id', 14);

            $table->char('entity_id', 14);

            $table->string('entity_type', 10); // org or roles

            $table->boolean('enable_workflow')
                  ->default(0);

            $table->unique(['permission_id', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::PERMISSION_MAP);
    }
}
