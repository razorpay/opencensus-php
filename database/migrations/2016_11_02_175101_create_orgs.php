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

            $table->char(Org::ID, Org::ID_LENGTH)
                  ->primary();

            $table->string(Org::BUSINESS_NAME);

            $table->string(Org::DISPLAY_NAME);

            $table->string(Org::EMAIL)
                  ->unique();

            $table->string(Org::AUTH_TYPE);

            $table->text(Org::EMAIL_DOMAINS);

            $table->boolean(Org::ALLOW_SIGN_UP)
                  ->default(0);

            $table->boolean(Org::CROSS_ORG_ACCESS)
                  ->default(0);

            $table->string(Org::LOGIN_LOGO_URL)
                  ->nullable();

            $table->string(Org::MAIN_LOGO_URL)
                  ->nullable();

            $table->string(Org::INVOICE_LOGO_URL)
                  ->nullable();

            $table->string(Org::CUSTOM_CODE, 255)
                  ->nullable()
                  ->unique();

            $table->string(Org::FROM_EMAIL)
                  ->nullable();

            $table->string(Org::SIGNATURE_EMAIL)
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
