<?php

use RZP\Constants\Table;

use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Merchant\Entity as Merchant;

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

            $table->char(Org::ID, 14)
                  ->primary();

            $table->string(Org::BUSINESS_NAME, 250);

            $table->string(Org::DISPLAY_NAME, 250);

            $table->string(Org::EMAIL, 250)
                  ->unique();

            $table->string(Org::AUTH_TYPE, 250);

            $table->text(Org::EMAIL_DOMAINS);

            $table->string(Org::LOGIN_LOGO_URL, 250)
                  ->nullable();
            $table->string(Org::MAIN_LOGO_URL, 250)
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Org::CREATED_AT);
            $table->integer(Org::UPDATED_AT);
            $table->integer(Org::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->index(Org::CREATED_AT);
            $table->index(Org::UPDATED_AT);
            $table->index(Org::DELETED_AT);
            $table->index(Org::EMAIL);
        });

        Schema::table(Table::MERCHANT, function(Blueprint $table)
        {
            $table->foreign(Merchant::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG)
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
        Schema::table(Table::MERCHANT, function($table)
        {
            $table->dropForeign(
                Table::MERCHANT.'_'.Merchant::ORG_ID.'_foreign');
        });

        Schema::drop(Table::ORG);
    }
}
