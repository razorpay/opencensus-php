<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Admins\Entity as Admins;

class CreateAdmins extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADMIN, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Admins::ID, 14)
                  ->primary();

            $table->string(Admins::EMAIL, 250);
            $table->string(Admins::NAME, 250);
            $table->string(Admins::USERNAME, 250);
            $table->string(Admins::PASSWORD, 250);
            $table->string(Admins::REMEMBER_TOKEN, 250);
            $table->string(Admins::OAUTH_ACCESS_TOKEN, 250);
            $table->string(Admins::OAUTH_PROVIDER_ID, 250);
            $table->char(Admins::ORG_ID, 14);

            $table->integer(Admins::CREATED_AT);
            $table->integer(Admins::UPDATED_AT);
            $table->integer(Admins::DELETED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ADMIN);
    }
}
