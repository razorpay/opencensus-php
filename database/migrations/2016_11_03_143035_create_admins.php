<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Admin\Entity as Admin;
use RZP\Models\Admin\Org\Entity as Org;

class CreateAdmins extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADMIN, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Admin::ID, Admin::ID_LENGTH)
                  ->primary();

            $table->char(Admin::ORG_ID, Admin::ID_LENGTH);

            $table->string(Admin::EMAIL);

            $table->string(Admin::NAME);

            $table->string(Admin::USERNAME)
                  ->nullable();

            $table->string(Admin::PASSWORD)
                  ->nullable();

            $table->string(Admin::REMEMBER_TOKEN)
                  ->nullable();

            $table->string(Admin::OAUTH_ACCESS_TOKEN)
                  ->nullable();

            $table->string(Admin::OAUTH_PROVIDER_ID)
                  ->nullable();

            $table->string(Admin::USER_TYPE)
                  ->nullable();

            $table->string(Admin::EMPLOYEE_CODE)
                  ->nullable();

            $table->string(Admin::BRANCH_CODE)
                  ->nullable();

            $table->string(Admin::DEPARTMENT_CODE)
                  ->nullable();

            $table->string(Admin::SUPERVISOR_CODE)
                  ->nullable();

            $table->string(Admin::LOCATION_CODE)
                  ->nullable();

            $table->integer(Admin::WRONG_2FA_ATTEMPTS)
                ->default(0);

            // account disabled by supervisor
            $table->boolean(Admin::DISABLED)
                  ->default(0);

            // admin can see all the merchants
            $table->boolean(Admin::ALLOW_ALL_MERCHANTS)
                  ->default(0);

            // user account has been locked due to
            // max password failure attempts
            $table->boolean(Admin::LOCKED)
                  ->default(0);

            $table->text(Admin::OLD_PASSWORDS)
                  ->nullable();

            $table->integer(Admin::LAST_LOGIN_AT)
                  ->nullable();

            $table->integer(Admin::FAILED_ATTEMPTS)
                  ->unsigned()
                  ->default(0);

            $table->integer(Admin::PASSWORD_EXPIRY)
                  ->nullable();

            $table->integer(Admin::PASSWORD_CHANGED_AT)
                  ->nullable();

            $table->string(Admin::PASSWORD_RESET_TOKEN)
                ->nullable();

            $table->integer(Admin::PASSWORD_RESET_EXPIRY)
                ->nullable();

            // When hit make Admin::DISABLED=1
            $table->integer(Admin::EXPIRED_AT)
                  ->nullable();

            $table->integer(Admin::CREATED_AT);
            $table->integer(Admin::UPDATED_AT);
            $table->integer(Admin::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->foreign(Admin::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG);

            $table->index(Admin::EMAIL);
            $table->index(Admin::CREATED_AT);
            $table->index(Admin::LAST_LOGIN_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ADMIN, function($table)
        {
            $table->dropForeign(Table::ADMIN . '_' . Admin::ORG_ID . '_foreign');
        });

        Schema::drop(Table::ADMIN);
    }
}
