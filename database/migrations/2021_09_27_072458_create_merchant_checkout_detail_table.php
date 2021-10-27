<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\CheckoutDetail\Entity;

class CreateMerchantCheckoutDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_CHECKOUT_DETAIL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->string(Entity::STATUS_1CC, 30)
                ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

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
        Schema::dropIfExists(Table::MERCHANT_CHECKOUT_DETAIL);
    }
}
