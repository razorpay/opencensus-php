<?php

use RZP\Constants\Table;
use RZP\Models\User\Entity as User;
use Illuminate\Database\Schema\Blueprint;
use RZP\Models\Merchant\Entity as Merchant;
use Illuminate\Database\Migrations\Migration;

class CreateMerchantUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_USERS, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Merchant::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(User::USER_ID, User::ID_LENGTH);

            $table->string(Merchant::PRODUCT, 255)
                  ->default('primary');

            $table->string(User::ROLE);

            $table->integer(User::CREATED_AT);
            $table->integer(User::UPDATED_AT);

            $table->foreign(User::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(User::USER_ID)
                  ->references(User::ID)
                  ->on(Table::USER)
                  ->on_delete('restrict');

            $table->unique([Merchant::MERCHANT_ID, Merchant::PRODUCT, User::USER_ID, User::ROLE]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MERCHANT_USERS, function(Blueprint $table)
        {
            $table->dropForeign(Table::MERCHANT_USERS .'_' .User::MERCHANT_ID .'_foreign');

            $table->dropForeign(Table::MERCHANT_USERS .'_' .User::USER_ID .'_foreign');

            $table->dropUnique('merchant_users_merchant_id_product_user_id_role_unique');
        });

        Schema::drop(Table::MERCHANT_USERS);
    }
}
