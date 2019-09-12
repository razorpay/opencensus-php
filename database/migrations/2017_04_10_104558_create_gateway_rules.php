<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

use RZP\Constants\Table;
use RZP\Models\Gateway\Rule\Entity as Rule;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Admin\Org\Entity as Org;

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

            $table->string(Rule::MERCHANT_ID, Merchant::ID_LENGTH)
                  ->nullable();

            $table->string(Rule::ORG_ID, Org::ID_LENGTH)
                  ->default(Org::RAZORPAY_ORG_ID);

            $table->string(Rule::PROCURER)
                  ->nullable();

            $table->string(Rule::GATEWAY, 50)
                  ->nullable();

            $table->string(Rule::TYPE, 10);

            $table->string(Rule::GROUP, 50)
                  ->nullable();

            $table->string(Rule::FILTER_TYPE, 10)
                  ->nullable();

            $table->integer(Rule::LOAD)
                  ->nullable();

            $table->string(Rule::GATEWAY_ACQUIRER)
                  ->nullable();

            $table->string(Rule::NETWORK_CATEGORY)
                  ->nullable();

            $table->tinyInteger(Rule::SHARED_TERMINAL)
                  ->nullable();

            $table->tinyInteger(Rule::INTERNATIONAL)
                  ->nullable();

            $table->tinyInteger(Rule::RECURRING)
                  ->nullable();

            $table->tinyInteger(Rule::CAPABILITY)
                  ->nullable();

            $table->string(Rule::RECURRING_TYPE, 50)
                  ->nullable();

            $table->string(Rule::NETWORK, 10)
                  ->nullable();

            $table->string(Rule::METHOD, 30);

            $table->string(Rule::METHOD_TYPE, 10)
                  ->nullable();

            $table->string(Rule::METHOD_SUBTYPE, 10)
                  ->nullable();

            $table->string(Rule::CARD_CATEGORY, 255)
                  ->nullable();

            $table->string(Rule::ISSUER)
                  ->nullable();

            $table->integer(Rule::MIN_AMOUNT)
                  ->default(0)
                  ->unsigned();

            $table->integer(Rule::MAX_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->text(Rule::IINS)
                  ->nullable();

            $table->string(Rule::CURRENCY, 3)
                  ->nullable();

            $table->tinyInteger(Rule::EMI_DURATION)
                  ->nullable();

            $table->string(Rule::EMI_SUBVENTION, 20)
                  ->nullable();

            $table->string(Rule::CATEGORY)
                  ->nullable();

            $table->string(Rule::CATEGORY2)
                  ->nullable();

            $table->string(Rule::AUTHENTICATION_GATEWAY)
                  ->nullable();

            $table->string(Rule::AUTH_TYPE, 14)
                  ->nullable();

            $table->string(Rule::STEP);

            $table->text(Rule::COMMENTS)
                  ->nullable();

            $table->integer(Rule::CREATED_AT);

            $table->integer(Rule::UPDATED_AT);

            $table->integer(Rule::DELETED_AT)
                  ->nullable();

            $table->index(Rule::TYPE);
            $table->index(Rule::FILTER_TYPE);
            $table->index(Rule::GROUP);
            $table->index(Rule::CURRENCY);
            $table->index(Rule::MIN_AMOUNT);
            $table->index(Rule::MAX_AMOUNT);
            $table->index(Rule::EMI_DURATION);
            $table->index(Rule::EMI_SUBVENTION);
            $table->index(Rule::GATEWAY);
            $table->index(Rule::GATEWAY_ACQUIRER);
            $table->index(Rule::INTERNATIONAL);
            $table->index(Rule::SHARED_TERMINAL);
            $table->index(Rule::NETWORK_CATEGORY);
            $table->index(Rule::CATEGORY);
            $table->index(Rule::CATEGORY2);
            $table->index(Rule::NETWORK);
            $table->index(Rule::METHOD);
            $table->index(Rule::METHOD_TYPE);
            $table->index(Rule::ISSUER);
            $table->index(Rule::DELETED_AT);
            $table->index(Rule::CREATED_AT);

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
