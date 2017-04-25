<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

use RZP\Constants\Table;
use RZP\Models\Gateway\LoadRule\Entity as LoadRule;
use RZP\Models\Merchant\Entity as Merchant;

class CreateGatewayLoadRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GATEWAY_LOAD_RULE, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(LoadRule::ID, LoadRule::ID_LENGTH)
                    ->primary();

            $table->string(LoadRule::GATEWAY, LoadRule::LENGTHS[LoadRule::GATEWAY]);

            $table->string(LoadRule::MERCHANT_ID, LoadRule::ID_LENGTH);

            $table->string(LoadRule::GATEWAY_ACQUIRER)
                    ->nullable();

            $table->tinyInteger(LoadRule::INTERNATIONAL)
                    ->default(0);

            $table->string(LoadRule::NETWORK, LoadRule::LENGTHS[LoadRule::NETWORK])
                    ->nullable();

            $table->string(LoadRule::METHOD, LoadRule::LENGTHS[LoadRule::METHOD]);

            $table->string(LoadRule::CARD_TYPE, LoadRule::LENGTHS[LoadRule::CARD_TYPE])
                    ->nullable();

            $table->string(LoadRule::ISSUER)
                    ->nullable();

            $table->integer(LoadRule::LOAD)
                    ->default(0);

            $table->integer(LoadRule::CREATED_AT);

            $table->integer(LoadRule::UPDATED_AT);

            $table->integer(LoadRule::DELETED_AT)
                    ->nullable();

            $table->foreign(LoadRule::MERCHANT_ID)
                    ->references(Merchant::ID)
                    ->on(Table::MERCHANT)
                    ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::LOAD_RULE, function ($table)
        {
            $table->dropForeign(
                Table::LOAD_RULE . '_' . LoadRule::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::LOAD_RULE);
    }
}
