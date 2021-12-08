<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\CorporateCard;
use RZP\Models\Merchant;

class CreateCorporateCardsTable extends Migration
{
    const UTF8MB4 = 'utf8mb4';

    const UTF8MB4_BIN = 'utf8mb4_bin';

    /**
     * Run the migrations.
     * Production DBA Ticket - https://jira.corp.razorpay.com/browse/DBOPS-1067
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CORPORATE_CARD, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(CorporateCard\Entity::ID, CorporateCard\Entity::ID_LENGTH)
                ->charset(self::UTF8MB4)
                ->collation(self::UTF8MB4_BIN);

            $table->char(CorporateCard\Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH)
                ->charset(self::UTF8MB4)
                ->collation(self::UTF8MB4_BIN);

            $table->string(CorporateCard\Entity::NAME, 50);
            $table->string(CorporateCard\Entity::HOLDER_NAME, 50);
            $table->char(CorporateCard\Entity::LAST4, 4);
            $table->string(CorporateCard\Entity::VAULT_TOKEN, 50);
            $table->char(CorporateCard\Entity::EXPIRY_MONTH, 2);
            $table->char(CorporateCard\Entity::EXPIRY_YEAR, 4);
            $table->string(CorporateCard\Entity::NETWORK, 50)
                ->nullable();
            $table->string(CorporateCard\Entity::BILLING_CYCLE, 50)
                ->nullable();
            $table->string(CorporateCard\Entity::ISSUER, 50)
                ->nullable();
            $table->tinyInteger(CorporateCard\Entity::IS_ACTIVE)
                ->default(1);

            $table->integer(CorporateCard\Entity::CREATED_AT);
            $table->integer(CorporateCard\Entity::UPDATED_AT);
            $table->char(CorporateCard\Entity::CREATED_BY, 20)
                ->nullable();
            $table->char(CorporateCard\Entity::UPDATED_BY, 20)
                ->nullable();

            $table->index(CorporateCard\Entity::MERCHANT_ID);
            $table->primary(CorporateCard\Entity::ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::CORPORATE_CARD);
    }
}
