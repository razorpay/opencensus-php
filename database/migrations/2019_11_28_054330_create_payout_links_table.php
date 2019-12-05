<?php

use RZP\Models\Batch;
use RZP\Constants\Table;
use RZP\Models\PayoutLink\Entity;
use RZP\Models\Currency\Currency;
use RZP\Models\User\Entity as User;
use Illuminate\Support\Facades\Schema;
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

            $table->string(Entity::SHORT_URL, 255)
                   ->nullable();

            $table->char(Entity::CONTACT_ID, Contact::ID_LENGTH);

            $table->char(Entity::FUND_ACCOUNT_ID, FundAccount::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(Entity::USER_ID, User::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::BATCH_ID, Batch\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::IDEMPOTENCY_KEY, Batch\Entity::IDEMPOTENCY_ID_LENGTH)
                  ->nullable();

            $table->string(Entity::STATUS, 40);

            $table->bigInteger(Entity::AMOUNT);

            $table->text(Entity::NOTES);

            $table->char(Entity::DESCRIPTION, 255)
                  ->nullable();

            $table->string(Entity::RECEIPT, 40)
                  ->nullable();

            $table->string(\RZP\Models\PaymentLink\Entity::CURRENCY, 3);

            $table->integer(Entity::CANCELLED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::CANCELLED_AT);

            $table->index(Entity::ID);

            $table->index(Entity::RECEIPT);

            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);

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
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payout_links');
    }
}
