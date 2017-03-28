<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\AdminLead\Entity as AdminLead;
use RZP\Models\Admin\Admin\Entity as Admin;
use RZP\Models\Admin\Org\Entity as Org;

class CreateAdminLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADMIN_LEAD, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(AdminLead::ID, AdminLead::ID_LENGTH)
                  ->primary();

            $table->char(AdminLead::ADMIN_ID, Admin::ID_LENGTH);

            $table->char(AdminLead::ORG_ID, Org::ID_LENGTH);

            $table->char(AdminLead::TOKEN, 40)
                  ->unique();

            $table->string(AdminLead::EMAIL);

            $table->text(AdminLead::FORM_DATA);

            $table->integer(AdminLead::CREATED_AT);
            $table->integer(AdminLead::UPDATED_AT);
            $table->integer(AdminLead::DELETED_AT)
                  ->nullable();

            $table->foreign(AdminLead::ADMIN_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN);

            $table->foreign(AdminLead::ORG_ID)
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
        Schema::table(Table::ADMIN_LEAD, function($table)
        {
            $table->dropForeign(
                Table::ADMIN_LEAD . '_' . AdminLead::ORG_ID . '_foreign');

            $table->dropForeign(
                Table::ADMIN_LEAD . '_' . AdminLead::ADMIN_ID . '_foreign');
        });

        Schema::drop(Table::ADMIN_LEAD);
    }
}
