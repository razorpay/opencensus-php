<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;

use Models\Merchant\Entity as Merchant;

class CreateMerchants extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Merchant::ID, Merchant::ID_LENGTH)
                  ->primary();

            $table->string(Merchant::NAME);

            $table->string(Merchant::EMAIL, 255)
                  ->unique();

            $table->boolean(Merchant::ACTIVATED)
                  ->default(0);

            $table->integer(Merchant::ACTIVATED_AT)
                  ->nullable();

            $table->boolean(Merchant::LIVE)
                  ->default(0);

            $table->boolean(Merchant::HOLD_FUNDS)
                  ->default(0);

            $table->char(Merchant::PRICING_PLAN_ID, Merchant::ID_LENGTH)
                  ->nullable();

            $table->string(Merchant::WEBSITE)
                  ->nullable();

            $table->char(Merchant::CATEGORY, 4)
                  ->nullable();

            $table->boolean(Merchant::INTERNATIONAL)
                  ->default(0);

            $table->string(Merchant::BILLING_LABEL)
                  ->nullable();

            $table->string(Merchant::RECEIPT_EMAIL_ENABLED)
                  ->default(1);

            $table->string(Merchant::TRANSACTION_REPORT_EMAIL)
                  ->nullable();

            $table->integer(Merchant::CREATED_AT);
            $table->integer(Merchant::UPDATED_AT);

            $table->index(Merchant::ACTIVATED_AT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MERCHANT);
    }
}
