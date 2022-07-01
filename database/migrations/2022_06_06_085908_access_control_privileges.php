<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\AccessControlPrivileges\Entity;
use RZP\Constants\Table;


class AccessControlPrivileges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create(Table::ACCESS_CONTROL_PRIVILEGES , function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, 14)
                ->primary();

            $table->string(Entity::NAME, 100);

            $table->string(Entity::DESCRIPTION, 255)
                ->nullable();

            $table->char(Entity::PARENT_ID, 14)
                ->nullable();

            $table->tinyInteger(Entity::VISIBILITY);

            $table->json(Entity::EXTRA_DATA)->nullable();

            $table->string(Entity::LABEL, 100);

            $table->integer(Entity::VIEW_POSITION)->default(10000);

            $table->bigInteger(Entity::CREATED_AT);

            $table->bigInteger(Entity::UPDATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists(Table::ACCESS_CONTROL_PRIVILEGES);
    }
}
