<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Partner\Config\Entity;
use RZP\Models\Pricing\Entity as Pricing;
use RZP\Models\Merchant\Entity as Merchant;

class CreatePartnerConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PARTNER_CONFIG, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::ENTITY_TYPE);
            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH);

            $table->string(Entity::ORIGIN_TYPE)
                  ->nullable();
            $table->char(Entity::ORIGIN_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->tinyInteger(Entity::COMMISSIONS_ENABLED)
                  ->default(0);

            $table->char(Entity::DEFAULT_PLAN_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::IMPLICIT_PLAN_ID, Entity::ID_LENGTH)
                  ->nullable();
            $table->integer(Entity::IMPLICIT_EXPIRY_AT)
                  ->nullable();

            $table->char(Entity::EXPLICIT_PLAN_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->tinyInteger(Entity::EXPLICIT_REFUND_FEES)
                  ->default(0);

            $table->tinyInteger(Entity::EXPLICIT_SHOULD_CHARGE)
                  ->default(0);

            $table->integer(Entity::REVISIT_AT);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index([Entity::ENTITY_TYPE, Entity::ENTITY_ID]);

            $table->index([Entity::ORIGIN_TYPE, Entity::ORIGIN_ID]);

            $table->foreign(Entity::DEFAULT_PLAN_ID)
                  ->references(Pricing::PLAN_ID)
                  ->on(Table::PRICING);

            $table->foreign(Entity::IMPLICIT_PLAN_ID)
                  ->references(Pricing::PLAN_ID)
                  ->on(Table::PRICING);

            $table->foreign(Entity::EXPLICIT_PLAN_ID)
                  ->references(Pricing::PLAN_ID)
                  ->on(Table::PRICING);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            Table::PARTNER_CONFIG, function ($table) {
                $table->dropForeign(Table::PARTNER_CONFIG.'_'.Entity::DEFAULT_PLAN_ID.'_foreign');
                $table->dropForeign(Table::PARTNER_CONFIG.'_'.Entity::IMPLICIT_PLAN_ID.'_foreign');
                $table->dropForeign(Table::PARTNER_CONFIG.'_'.Entity::EXPLICIT_PLAN_ID.'_foreign');
            }
        );
        Schema::dropIfExists(Table::PARTNER_CONFIG);
    }
}
