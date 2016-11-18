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

            $table->char(Admin::ID, 14)
                  ->primary();

            $table->string(Admin::EMAIL, 250);

            $table->string(Admin::NAME, 250);

            $table->string(Admin::USERNAME, 250)
                  ->nullable();

            $table->string(Admin::PASSWORD, 250)
                  ->nullable();

            $table->string(Admin::REMEMBER_TOKEN, 250)
                  ->nullable();

            $table->string(Admin::OAUTH_ACCESS_TOKEN, 250)
                  ->nullable();

            $table->string(Admin::OAUTH_PROVIDER_ID, 250)
                  ->nullable();

            $table->char(Admin::ORG_ID, 14);

            $table->string(Admin::USER_TYPE, 250)
                  ->nullable();

            $table->string(Admin::EMPLOYEE_CODE, 250);

            $table->string(Admin::BRANCH_CODE, 250);

            $table->string(Admin::DEPARTMENT_CODE, 250);

            $table->string(Admin::SUPERVISOR_CODE, 250);

            $table->string(Admin::LOCATION_CODE, 250);

            // account disabled by supervisor
            $table->boolean(Admin::DISABLED)
                  ->default(0);

            // use account has been locked due to max password failure attempts
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

            // When hit make Admin::DISABLED=1
            $table->integer(Admin::EXPIRY_AT)
                  ->nullable();

            $table->integer(Admin::CREATED_AT);
            $table->integer(Admin::UPDATED_AT);
            $table->integer(Admin::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->foreign(Admin::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG);
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
