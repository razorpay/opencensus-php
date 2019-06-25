<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Constants\Product;
use RZP\Models\Admin\Role\Entity as Role;
use RZP\Models\Admin\Org\Entity as Org;

class CreateRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ROLE, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Role::ID, Role::ID_LENGTH)
                  ->primary();

            $table->string(Role::NAME);

            $table->string(Role::DESCRIPTION);

            $table->string(Role::PRODUCT)
                  ->default(Product::PRIMARY);

            $table->char(Role::ORG_ID, Role::ID_LENGTH);

            $table->integer(Role::CREATED_AT);
            $table->integer(Role::UPDATED_AT);

            $table->integer(Role::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->unique([Role::NAME, Role::ORG_ID]);

            $table->foreign(Role::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG);

            $table->index(Role::CREATED_AT);
            $table->index(Role::PRODUCT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ROLE);
    }
}
