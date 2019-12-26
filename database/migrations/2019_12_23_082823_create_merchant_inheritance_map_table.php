<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\InheritanceMap\Entity;
use RZP\Models\Merchant\Entity as Merchant;

class CreateMerchantInheritanceMapTable extends Migration
{
    const MERCHANT_ID           = 'merchant_id';
    const PARENT_MERCHANT_ID    = 'parent_merchant_id';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_INHERITANCE_MAP, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
            ->primary();

            $table->char(self::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(self::PARENT_MERCHANT_ID, Merchant::ID_LENGTH);

            $table->foreign(self::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT);

            $table->foreign(self::PARENT_MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::DELETED_AT);

            $table->unique([self::MERCHANT_ID, self::PARENT_MERCHANT_ID]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MERCHANT_INHERITANCE_MAP, function($table)
        {
            $table->dropForeign(
                Table::MERCHANT_INHERITANCE_MAP.'_'.self::MERCHANT_ID.'_foreign');

            $table->dropForeign(
                Table::MERCHANT_INHERITANCE_MAP.'_'.self::PARENT_MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::MERCHANT_INHERITANCE_MAP);
    }
}
