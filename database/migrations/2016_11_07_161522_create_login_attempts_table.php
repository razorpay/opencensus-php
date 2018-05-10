<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\LoginAttempt\Entity as LoginAttempt;
use RZP\Models\Admin\Admin\Entity as Admin;

class CreateLoginAttemptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::LOGIN_ATTEMPT, function (Blueprint $table)
        {
            $table->increments(LoginAttempt::ID);

            $table->char(LoginAttempt::ADMIN_ID, LoginAttempt::ID_LENGTH);

            $table->boolean(LoginAttempt::VALID)->default(0);

            // Meta data
            $table->string(LoginAttempt::USER_AGENT)->nullable();

            $table->ipAddress(LoginAttempt::IP_ADDRESS)->nullable();

            $table->integer(LoginAttempt::CREATED_AT);

            $table->foreign(LoginAttempt::ADMIN_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN);

            $table->index(LoginAttempt::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::LOGIN_ATTEMPT);
    }
}
