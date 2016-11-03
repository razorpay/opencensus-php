<?php

use RZP\Constants\Table;

use RZP\Models\Admin\Organization\Entity as Organization;

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

            $table->string(Organization::AUTH, 255);

            $table->text(Organization::EMAIL_DOMAINS);

            $table->text(Organization::LOGO_URL);

            $table->string(Organization::EMAIL, 255);

            // Adds created_at and updated_at columns to the table
            $table->integer(Organization::CREATED_AT);
            $table->integer(Organization::UPDATED_AT);
            $table->integer(Organization::DELETED_AT);

            $table->index(Organization::CREATED_AT);
            $table->index(Organization::UPDATED_AT);
            $table->index(Organization::DELETED_AT);
            $table->index(Organization::EMAIL);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ORGANIZATION);
    }
}
