<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\RiskNotes\Entity as Entity;

class CreateMerchantRiskNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_RISK_NOTE, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH)
                ->nullable(false);

            $table->char(Entity::ADMIN_ID, Entity::ID_LENGTH)
                ->nullable(false);

            $table->text(Entity::NOTE)
                ->nullable(false);

            $table->integer(Entity::CREATED_AT)
                ->nullable(false);

            $table->integer(Entity::DELETED_AT)
                ->nullable();

            $table->char(Entity::DELETED_BY, Entity::ID_LENGTH)
                ->nullable();

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
        Schema::dropIfExists(Table::MERCHANT_RISK_NOTE);
    }
}
