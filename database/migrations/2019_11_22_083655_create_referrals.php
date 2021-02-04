<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Referral\Entity;

class CreateReferrals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::REFERRALS, function(Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->string(Entity::PRODUCT, 255)->default(\RZP\Constants\Product::PRIMARY);

            $table->string(Entity::REF_CODE, Entity::ID_LENGTH);

            $table->string(Entity::URL)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->unique([Entity::MERCHANT_ID, Entity::PRODUCT]);

            $table->unique(Entity::REF_CODE);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::REFERRALS);
    }
}
