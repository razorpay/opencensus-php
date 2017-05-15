<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Tax\Group\Entity;

class CreateTaxGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return
     */
    public function up()
    {
        Schema::create(Table::TAX_GROUP, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::NAME, 512);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::DELETED_AT);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return
     */
    public function down()
    {
        Schema::table(Table::TAX_GROUP, function($table)
        {
            $table->dropForeign
            (
                Table::TAX_GROUP . '_' . Entity::MERCHANT_ID . '_foreign'
            );
        });

        Schema::drop(Table::TAX_GROUP);
    }
}
