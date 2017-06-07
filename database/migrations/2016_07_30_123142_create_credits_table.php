<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits\Entity as Credits;

class CreateCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CREDITS, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Credits::ID, Credits::ID_LENGTH)
                  ->primary();

            $table->string(Credits::CAMPAIGN, 255);

            $table->char(Credits::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->char(Credits::PROMOTION_ID, Merchant\Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Credits::VALUE);

            $table->string(Credits::TYPE, 20);

            $table->tinyInteger(Credits::EXPIRED)
                  ->default(0);

            $table->integer(Credits::BALANCE)
                  ->default(0);

            $table->integer(Credits::CREATED_AT);
            $table->integer(Credits::UPDATED_AT);

            $table->index(Credits::CREATED_AT);
            $table->index(Credits::CAMPAIGN);
            $table->index(Credits::TYPE);

            $table->foreign(Credits::MERCHANT_ID)
                ->references(Merchant\Entity::ID)
                ->on(Table::MERCHANT)
                ->on_delete('restrict');

            $table->foreign(Credits::PROMOTION_ID)
                ->references(Promotion\Entity::ID)
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
        Schema::table(Table::CREDITS, function (Blueprint $table)
        {
            $table->dropForeign(
                Table::CREDITS.'_'.Credits::MERCHANT_ID.'_foreign');

            $table->dropForeign(
                Table::PROMOTION.'_'.Credits::PROMOTION_ID.'_foreign');
        });

        Schema::drop(Table::CREDITS);
    }
}
