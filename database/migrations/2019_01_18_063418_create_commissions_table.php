<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Pricing\Entity as Pricing;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Transaction\Entity as Transaction;
use RZP\Models\Partner\Config\Entity as PartnerConfig;
use RZP\Models\Partner\Commission\Entity as Commission;

class CreateCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::COMMISSION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Commission::ID, Commission::ID_LENGTH)
                  ->primary();

            $table->string(Commission::SOURCE_TYPE);

            $table->char(Commission::SOURCE_ID, PublicEntity::ID_LENGTH);

            $table->char(Commission::PARTNER_ID, Merchant::ID_LENGTH);

            $table->char(Commission::PARTNER_CONFIG_ID, ParterConfig::ID_LENGTH);

            $table->char(Commission::TRANSACTION_ID, Transaction::ID_LENGTH)
                  ->nullable();

            $table->bigInteger(Commission::DEBIT)
                  ->unsigned();

            $table->bigInteger(Commission::CREDIT)
                  ->unsigned();

            $table->char(Transaction::CURRENCY, 3);

            $table->integer(Commission::FEE)
                  ->unsigned()
                  ->nullable();

            $table->integer(Commission::TAX)
                  ->unsigned()
                  ->nullable();

            $table->string(Commission::STATUS);

            $table->text(Commission::NOTES);

            $table->integer(Commission::CREATED_AT);

            $table->integer(Commission::UPDATED_AT);

            $table->index(Commission::CREATED_AT);

            $table->index([Commission::SOURCE_TYPE, Commission::SOURCE_ID]);

            $table->index([Commission::SOURCE_TYPE, Commission::CREATED_AT]);

            $table->index([Commission::PARTNER_ID, Commission::CREATED_AT]);

            $table->index(Commission::PARTNER_CONFIG_ID);

            $table->index(Commission::PRICING_RULE_ID);

            $table->index(Commission::TRANSACTION_ID);

            $table->foreign(Commission::PARTNER_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Commission::PARTNER_CONFIG_ID)
                  ->references(PartnerConfig::ID)
                  ->on(Table::PARTNER_CONFIG)
                  ->on_delete('restrict');

            $table->foreign(Commission::PRICING_RULE_ID)
                  ->references(Pricing::ID)
                  ->on(Table::PRICING)
                  ->on_delete('restrict');

            $table->foreign(Commission::TRANSACTION_ID)
                  ->references(Transaction::ID)
                  ->on(Table::TRANSACTION)
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
        Schema::dropIfExists(Table::COMMISSION);
    }
}
