<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Invoice;
use RZP\Constants\Table;
use RZP\Models\User\Entity as User;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::USER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(User::ID, User::ID_LENGTH)->primary();

            $table->string(User::NAME, 200);

            $table->string(User::EMAIL, 255)->unique()->nullable();

            $table->string(User::PASSWORD, 100)->nullable();
            $table->string(User::OLD_PASSWORD_1, 100)->nullable();
            $table->string(User::OLD_PASSWORD_2, 100)->nullable();

            $table->string(User::CONTACT_MOBILE, 15)->nullable();

            $table->string(User::REMEMBER_TOKEN, 100)->nullable();

            $table->string(User::CONFIRM_TOKEN)->nullable();

            $table->string(User::PASSWORD_RESET_TOKEN)->nullable();

            $table->integer(User::PASSWORD_RESET_EXPIRY)->nullable();

            $table->tinyInteger(User::CONTACT_MOBILE_VERIFIED)
                  ->default(0);

            $table->tinyInteger(User::SECOND_FACTOR_AUTH)
                  ->default(0);

            $table->integer(User::WRONG_2FA_ATTEMPTS)
                  ->default(0);

            $table->tinyInteger(User::ACCOUNT_LOCKED)
                  ->default(0);

            $table->integer(User::CREATED_AT);

            $table->integer(User::UPDATED_AT);

            $table->json(User::OAUTH_PROVIDER)
                  ->nullable();

            $table->json(User::OLD_PASSWORDS)->nullable();

            $table->tinyInteger(User::SIGNUP_VIA_EMAIL)->default(1);

            $table->char(User::AUDIT_ID,User::ID_LENGTH)->nullable();

            $table->index(User::CONFIRM_TOKEN);

            $table->index(User::CREATED_AT);

            $table->index(User::CONTACT_MOBILE);
        });

        Schema::table(Table::INVOICE, function(Blueprint $table)
        {
            $table->foreign(Invoice\Entity::USER_ID)
                  ->references(User::ID)
                  ->on(Table::USER)
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

        Schema::table(Table::INVOICE, function($table)
        {
            $table->dropForeign
            (
                Table::INVOICE . '_' . Invoice\Entity::USER_ID . '_foreign'
            );
        });

        Schema::drop(Table::USER);
    }
}
