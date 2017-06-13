<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Base\PublicEntity;

class CreateTaxGroupTaxMap extends Migration
{
    const TAX_GROUP_ID = 'tax_group_id';
    const TAX_ID       = 'tax_id';

    /**
     * Run the migrations.
     *
     * @return
     */
    public function up()
    {
        Schema::create(Table::TAX_GROUP_TAX_MAP, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(self::TAX_GROUP_ID, PublicEntity::ID_LENGTH);

            $table->char(self::TAX_ID, PublicEntity::ID_LENGTH);

            $table->integer(PublicEntity::CREATED_AT);
            $table->integer(PublicEntity::UPDATED_AT);

            $table->index(PublicEntity::CREATED_AT);
            $table->index(PublicEntity::UPDATED_AT);

            $table->unique([self::TAX_GROUP_ID, self::TAX_ID]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return
     */
    public function down()
    {
        Schema::drop(Table::TAX_GROUP_TAX_MAP);
    }
}
