<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\AdminLead\Entity as AdminLead;
use RZP\Models\Admin\Admin\Entity as Admin;

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

            $table->char(AdminLead::ID, AdminLead::ID_LENGTH)->primary();

            $table->char(AdminLead::ADMIN_ID, AdminLead::ID_LENGTH);

            $table->string(AdminLead::TOKEN, 250)->unique();

            $table->string(AdminLead::EMAIL, 250);

            $table->text(AdminLead::FORM_DATA);

            $table->integer(AdminLead::CREATED_AT);
            $table->integer(AdminLead::UPDATED_AT);
            $table->integer(AdminLead::DELETED_AT)
                  ->nullable();

            $table->foreign(AdminLead::ADMIN_ID)
                  ->references(Admin::ID)
                  ->on(Table::ADMIN);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ADMIN_LEAD);
    }
}
