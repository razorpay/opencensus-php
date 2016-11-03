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
        Schema::create(Table::PERMISSION_MAP, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            
            $table->string('entity_type', 250);
            $table->char('entity_id', 14);
            $table->char('permission_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create(Table::PERMISSION_MAP);
    }
}
