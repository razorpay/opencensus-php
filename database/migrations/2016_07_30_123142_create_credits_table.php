<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Promotion;
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

            $table->char(Credits::PROMOTION_ID, Promotion\Entity::ID_LENGTH)
                  ->nullable();

            $table->bigInteger(Credits::VALUE);

            $table->string(Credits::TYPE, 20);

            $table->bigInteger(Credits::USED)
                  ->default(0);

            $table->integer(Credits::EXPIRED_AT)
                  ->nullable();

            $table->integer(Credits::CREATED_AT);
            $table->integer(Credits::UPDATED_AT);

            $table->string(Credits::IDEMPOTENCY_KEY, 255)
                  ->nullable();

            $table->string(Credits::BATCH_ID, Credits::ID_LENGTH)
                  ->nullable();

            $table->string(Credits::BALANCE_ID, Credits::ID_LENGTH)
                ->nullable();

            $table->string(Credits::CREATOR_NAME, 255)
                  ->nullable();

            $table->string(Credits::REMARKS, 255)
                  ->nullable();

            $table->string(Credits::PRODUCT, 255)
                  ->nullable();

            $table->index(Credits::CREATED_AT);
            $table->index(Credits::CAMPAIGN);
            $table->index(Credits::TYPE);
            $table->index(Credits::EXPIRED_AT);
            $table->index([Credits::MERCHANT_ID, Credits::BALANCE_ID]);

            $table->foreign(Credits::MERCHANT_ID)
                ->references(Merchant\Entity::ID)
                ->on(Table::MERCHANT)
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
        });

        Schema::drop(Table::CREDITS);
    }
}
