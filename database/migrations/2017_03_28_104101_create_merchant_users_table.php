<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\User\Entity as User;

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

            $table->char(Merchant::USER_ID, 14);

            $table->string('role');

            $table->integer(Merchant::CREATED_AT);
            $table->integer(Merchant::UPDATED_AT);

            $table->foreign(Merchant::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(User::USER_ID)
                  ->references(User::ID)
                  ->on(Table::USER)
                  ->on_delete('restrict');

            $table->unique([Merchant::MERCHANT_ID, User::USER_ID, 'role']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Merchant::MERCHANT_USERS, function(Blueprint $table)
        {
            $table->dropForeign('merchant_users_merchant_id_foreign');

            $table->dropForeign('merchant_users_user_id_foreign');

            $table->dropUnique('merchant_users_merchant_id_user_id_role_unique');
        });

        Schema::drop(Merchant::MERCHANT_USERS);
    }
}
