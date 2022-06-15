<?php

use RZP\Constants\Table;
use RZP\Models\Merchant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\Merchant1ccComments\Entity as Entity;

class CreateMerchant1ccCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_1CC_COMMENTS, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->string(Entity::FLOW, 255);

            $table->string(Entity::COMMENT, 1023);

            $table->bigInteger(Entity::CREATED_AT);

            $table->bigInteger(Entity::DELETED_AT)->nullable();

            $table->index(Entity::MERCHANT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_1CC_COMMENTS);
    }
}
