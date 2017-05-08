<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

use RZP\Constants\Table;
use RZP\Models\Gateway\Rule\Entity as Rule;
use RZP\Models\Merchant\Entity as Merchant;

class CreateGatewayRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GATEWAY_RULE, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Rule::ID, Rule::ID_LENGTH)
                    ->primary();

            $table->string(Rule::GATEWAY, Rule::LENGTHS[Rule::GATEWAY]);

            $table->string(Rule::MERCHANT_ID, Rule::ID_LENGTH);

            $table->string(Rule::GATEWAY_ACQUIRER)
                    ->nullable();

            $table->tinyInteger(Rule::INTERNATIONAL)
                    ->default(0);

            $table->string(Rule::NETWORK, Rule::LENGTHS[Rule::NETWORK])
                    ->nullable();

            $table->string(Rule::METHOD, Rule::LENGTHS[Rule::METHOD]);

            $table->string(Rule::METHOD_TYPE, Rule::LENGTHS[Rule::METHOD_TYPE])
                    ->nullable();

            $table->string(Rule::ISSUER)
                    ->nullable();

            $table->integer(Rule::LOAD)
                    ->default(0);

            $table->integer(Rule::CREATED_AT);

            $table->integer(Rule::UPDATED_AT);

            $table->integer(Rule::DELETED_AT)
                    ->nullable();

            $table->index(Rule::GATEWAY);

            $table->index(Rule::GATEWAY_ACQUIRER);

            $table->index(Rule::INTERNATIONAL);

            $table->index(Rule::NETWORK);

            $table->index(Rule::METHOD);

            $table->index(Rule::METHOD_TYPE);

            $table->index(Rule::ISSUER);

            $table->index(Rule::DELETED_AT);

            $table->foreign(Rule::MERCHANT_ID)
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
        Schema::table(Table::GATEWAY_RULE, function ($table)
        {
            $table->dropForeign(
                Table::GATEWAY_RULE . '_' . Rule::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::GATEWAY_RULE);
    }
}
