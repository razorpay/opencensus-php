<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Schedule;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Promotion\Entity as Promotion;
use RZP\Models\Merchant\Credits\Entity as Credits;

class CreatePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PROMOTION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Promotion::ID, Promotion::ID_LENGTH)
                  ->primary();

            $table->string(Promotion::NAME, 50);

            $table->string(Promotion::PRODUCT, 225)
                  ->nullable();

            $table->integer(Promotion::CREDIT_AMOUNT)
                  ->unsigned()
                  ->default(0);

            $table->string(Promotion::CREDIT_TYPE, 20);

            $table->char(Promotion::SCHEDULE_ID, Schedule\Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Promotion::ITERATIONS)
                  ->unsigned()
                  ->default(1);

            $table->tinyInteger(Promotion::CREDITS_EXPIRE)
                  ->default(0);

            $table->string(Promotion::PURPOSE,255)
                  ->nullable();

            $table->string(Promotion::CREATOR_NAME,255)
                  ->nullable();

            $table->char(Promotion::PRICING_PLAN_ID, PublicEntity::ID_LENGTH)
                  ->nullable();

            $table->char(Promotion::PARTNER_ID, Merchant::ID_LENGTH)
                  ->nullable();

            $table->char(Promotion::EVENT_ID, PublicEntity::ID_LENGTH)
                  ->nullable();

            $table->integer(Promotion::START_AT)
                  ->nullable();

            $table->integer(Promotion::END_AT)
                  ->nullable();

            $table->integer(Promotion::ACTIVATED_AT)
                  ->nullable();

            $table->integer(Promotion::DEACTIVATED_AT)
                  ->nullable();

            $table->string(Promotion::STATUS, 255)
                  ->nullable();

            $table->string(Promotion::DEACTIVATED_BY, 255)
                  ->nullable();

            $table->integer(Promotion::CREATED_AT);

            $table->integer(Promotion::UPDATED_AT);

            $table->string(Promotion::REFERENCE1, 255)
                  ->nullable();

            $table->string(Promotion::REFERENCE2, 255)
                  ->nullable();

            $table->string(Promotion::REFERENCE3, 255)
                  ->nullable();

            $table->string(Promotion::REFERENCE4, 255)
                  ->nullable();

            $table->string(Promotion::REFERENCE5, 255)
                  ->nullable();

            $table->foreign(Promotion::SCHEDULE_ID)
                  ->references(Schedule\Entity::ID)
                  ->on(Table::SCHEDULE)
                  ->on_delete('restrict');

            $table->foreign(Promotion::PARTNER_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });

        // This needs to be done here because migrations are run in order of
        // timestamps and promotions table gets created after credits.
        Schema::table(Table::CREDITS, function(Blueprint $table)
        {
            $table->foreign(Credits::PROMOTION_ID)
                  ->references(Promotion::ID)
                  ->on(Table::PROMOTION)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::PROMOTION, function($table)
        {
            $table->dropForeign(Table::PROMOTION.'_'.Promotion::SCHEDULE_ID.'_foreign');
            $table->dropForeign(Table::PROMOTION.'_'.Promotion::PARTNER_ID.'_foreign');
        });

        Schema::table(Table::CREDITS, function($table)
        {
            $table->dropForeign(
                Table::CREDITS.'_'.Credits::PROMOTION_ID.'_foreign');
        });

        Schema::drop(Table::PROMOTION);
    }
}
