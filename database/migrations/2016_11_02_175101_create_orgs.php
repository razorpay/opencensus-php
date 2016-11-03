<?php

use RZP\Constants\Table;

use RZP\Models\Admin\Org\Entity as Orgs;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrgs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create(Table::ORG, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Orgs::ID, 14)
                  ->primary();

            $table->string(Orgs::BUSINESS_NAME, 250);

            $table->string(Orgs::DISPLAY_NAME, 250);

            $table->string(Orgs::EMAIL, 250);

            $table->string(Orgs::AUTH_TYPE, 250);

            $table->text(Orgs::EMAIL_DOMAINS);

            $table->text(Orgs::LOGO_URL);

            // Adds created_at and updated_at columns to the table
            $table->integer(Orgs::CREATED_AT);
            $table->integer(Orgs::UPDATED_AT);
            $table->integer(Orgs::DELETED_AT);

            $table->index(Orgs::CREATED_AT);
            $table->index(Orgs::UPDATED_AT);
            $table->index(Orgs::DELETED_AT);
            $table->index(Orgs::EMAIL);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ORG);
    }
}
