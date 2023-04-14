<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\VerificationDetail\Entity;

class CreateMerchantVerificationDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_VERIFICATION_DETAIL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->string(Entity::ARTEFACT_TYPE, 255);

            $table->string(Entity::ARTEFACT_IDENTIFIER, 30);

            $table->string(Entity::STATUS, 30)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->json(Entity::METADATA)->nullable();

            $table->char(Entity::AUDIT_ID, Entity::ID_LENGTH)->nullable();

            $table->index(Entity::MERCHANT_ID);

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
        Schema::dropIfExists(Table::MERCHANT_VERIFICATION_DETAIL);
    }
}
