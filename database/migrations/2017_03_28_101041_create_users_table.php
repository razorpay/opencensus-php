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

            $table->string(User::EMAIL, 255)->unique();

            $table->string(User::PASSWORD, 100);

            $table->string(User::CONTACT_MOBILE, 15)->nullable();

            $table->string(User::REMEMBER_TOKEN, 100)->nullable();

            $table->string(User::CONFIRM_TOKEN)->nullable();

            $table->integer(User::CREATED_AT);

            $table->integer(User::UPDATED_AT);
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
