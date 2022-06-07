<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\AccessPolicyAuthzRolesMap\Entity;

class AccessPolicyAuthzRolesMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create(Table::ACCESS_POLICY_AUTHZ_ROLES_MAP , function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, 14)
                ->primary();

            $table->char(Entity::PRIVILEGE_ID, 14);

            $table->string(Entity::ACTION, 25);

            $table->json(Entity::AUTHZ_ROLES)->nullable();

            $table->json(Entity::META_DATA)->nullable();

            $table->bigInteger(Entity::CREATED_AT);

            $table->bigInteger(Entity::UPDATED_AT);

            $table->unique([Entity::PRIVILEGE_ID, Entity::ACTION]);

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
        Schema::dropIfExists(Table::ACCESS_POLICY_AUTHZ_ROLES_MAP);
    }
}
