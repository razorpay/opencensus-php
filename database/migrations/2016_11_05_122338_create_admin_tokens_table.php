<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Admin\Token\Entity as AdminToken;
use RZP\Models\Admin\Admin\Entity as Admin;

class CreateAdminTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADMIN_TOKEN, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(AdminToken::ID, AdminToken::ID_LENGTH)
                  ->primary();

            $table->char(AdminToken::ADMIN_ID, 14);

            $table->string(AdminToken::TOKEN, 250)->unique();

            $table->integer(AdminToken::CREATED_AT);
            $table->integer(AdminToken::UPDATED_AT);
            $table->integer(AdminToken::EXPIRES_AT)->nullable();

            $table->foreign(AdminToken::ADMIN_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN);

            $table->index(AdminToken::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ADMIN_TOKEN);
    }
}
