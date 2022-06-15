<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\RoleAccessPolicyMap\Entity;

class CreateRoleAccessPolicyMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ROLE_ACCESS_POLICY_MAP, function (Blueprint $table) {

            $table->char(Entity::ID, 14)
                ->primary();

            $table->char(Entity::ROLE_ID, 30);

            $table->json(Entity::AUTHZ_ROLES);

            $table->json(Entity::ACCESS_POLICY_IDS);

            $table->bigInteger(Entity::CREATED_AT);

            $table->bigInteger(Entity::UPDATED_AT);

            $table->index(Entity::ROLE_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::ROLE_ACCESS_POLICY_MAP);
    }
}
