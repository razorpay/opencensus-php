<?php

use RZP\Models\Batch;
use RZP\Constants\Table;
use RZP\Models\Merchant\Balance;
use RZP\Models\PayoutLink\Entity;
use RZP\Models\User\Entity as User;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Payout\Entity as Payout;
use RZP\Models\Contact\Entity as Contact;
use Illuminate\Database\Schema\Blueprint;
use RZP\Models\Merchant\Entity as Merchant;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\FundAccount\Entity as FundAccount;

class CreatePayoutLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYOUT_LINK, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            // This is nullable in the database, but will be handled on the app level.
            // We will not be creating payout_links entity, without generating short_url first

            $table->string(Entity::SHORT_URL, 255)
                  ->nullable();

            $table->char(Entity::CONTACT_ID, Contact::ID_LENGTH);

            $table->char(Entity::CONTACT_NAME, 255)
                  ->nullable();

            $table->char(Entity::CONTACT_EMAIL, 255)
                  ->nullable();

            $table->char(Entity::CONTACT_PHONE_NUMBER, 255)
                  ->nullable();

            $table->char(Entity::FUND_ACCOUNT_ID, FundAccount::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::BALANCE_ID, Balance\Entity::ID_LENGTH);

            $table->char(Entity::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(Entity::USER_ID, User::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::BATCH_ID, Batch\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::IDEMPOTENCY_KEY, Batch\Entity::IDEMPOTENCY_ID_LENGTH)
                  ->nullable();

            $table->string(Entity::STATUS, 40);

            $table->bigInteger(Entity::AMOUNT);

            $table->string(Entity::CURRENCY, 3);

            $table->boolean(Entity::SEND_SMS)
                  ->default(false);

            $table->boolean(Entity::SEND_EMAIL)
                  ->default(false);

            $table->text(Entity::NOTES)
                  ->nullable();

            $table->string(Entity::PURPOSE, 255);

            $table->char(Entity::DESCRIPTION, 255)
                  ->nullable();

            $table->string(Entity::RECEIPT, 40)
                  ->nullable();

            $table->integer(Entity::CANCELLED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::USER_ID);

            $table->index(Payout::BALANCE_ID, Entity::MERCHANT_ID);

            $table->index(Entity::BATCH_ID);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::DELETED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::CANCELLED_AT);

            $table->index(Entity::RECEIPT);

            $table->index(Entity::STATUS);

            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);

            // todo, pl uncomment this after going live, to skip FKs entry in prod DB
            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Entity::CONTACT_ID)
                  ->references(Contact::ID)
                  ->on(Table::CONTACT)
                  ->on_delete('restrict');

            $table->foreign(Entity::FUND_ACCOUNT_ID)
                  ->references(FundAccount::ID)
                  ->on(Table::FUND_ACCOUNT)
                  ->on_delete('restrict');

            $table->foreign(Entity::BALANCE_ID)
                  ->references(Balance\Entity::ID)
                  ->on(Table::BALANCE)
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
        Schema::table(Table::PAYOUT_LINK, function($table)
        {
            $table->dropForeign(Table::PAYOUT_LINK . '_' . Entity::MERCHANT_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT_LINK . '_' . Entity::CONTACT_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT_LINK . '_' . Entity::FUND_ACCOUNT_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT_LINK . '_' . Entity::BALANCE_ID . '_foreign');
        });

        Schema::table(Table::PAYOUT, function($table)
        {
            $table->dropForeign(Table::PAYOUT . '_' . \RZP\Models\Payout\Entity::PAYOUT_LINK_ID . '_foreign');
        });

        Schema::dropIfExists(Table::PAYOUT_LINK);
    }
}
