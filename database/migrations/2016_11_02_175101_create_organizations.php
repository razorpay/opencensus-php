<?php

use RZP\Constants\Table;

use RZP\Models\Admin\Organization\Entity as Organization;
use RZP\Models\Admin\Entity as Admin;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrganizations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create(Table::ORGANIZATION, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Organization::ID, 14)
                  ->primary();

            $table->text(Organization::AUTH_TYPE);

            $table->text(Organization::ALLOWED_EMAIL_DOMAINS);

            $table->text(Organization::LOGO_URL);

            $table->string(Organization::EMAIL, 255);

            $table->char(Organization::OWNER_ID, Organization::ID_LENGTH);

            // Adds created_at and updated_at columns to the table
            $table->integer(Organization::CREATED_AT);
            $table->integer(Organization::UPDATED_AT);
            $table->integer(Organization::DELETED_AT);

            $table->index(Organization::CREATED_AT);
            $table->index(Organization::UPDATED_AT);
            $table->index(Organization::DELETED_AT);
            $table->index(Organization::EMAIL);

            $table->foreign(Organization::OWNER_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN)
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
        Schema::table(Table::ORGANIZATION, function($table)
        {
            $table->dropForeign(
                Table::ORGANIZATION.'_'.Organization::OWNER_ID.'_foreign');
        });

        Schema::drop(Table::ORGANIZATION);
    }
}
