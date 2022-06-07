<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Roles\Entity;
use RZP\Constants\Table;

class CreateAccessControlRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ACCESS_CONTROL_ROLES, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, 30)
                ->primary();
            $table->char(Entity::MERCHANT_ID, 14);

            $table->string(Entity::NAME, 100);

            $table->string(Entity::DESCRIPTION, 255)
                ->nullable();

            $table->string(Entity::TYPE, 50);

            $table->string(Entity::CREATED_BY, 100);
            $table->string(Entity::UPDATED_BY, 100);

            $table->char(Entity::ORG_ID, 14)->default(Entity::ORG_ID_FOR_ROLES);
            $table->string(Entity::PRODUCT, 50)->default('banking');

            $table->bigInteger(Entity::CREATED_AT);
            $table->bigInteger(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_ID);
            $table->index(Entity::TYPE);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::ACCESS_CONTROL_ROLES);
    }
}
