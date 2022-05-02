<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Base\Audit\Entity as Entity;

class CreateAuditInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::AUDIT_INFO, function(BluePrint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH);

            $table->json(Entity::META)->nullable(false);;

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->primary([Entity::ID, Entity::CREATED_AT]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::AUDIT_INFO);
    }
}
