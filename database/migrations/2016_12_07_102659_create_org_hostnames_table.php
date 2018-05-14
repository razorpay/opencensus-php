<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Org\Hostname\Entity as OrgHost;
use RZP\Models\Admin\Org\Entity as Org;

class CreateOrgHostnamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ORG_HOSTNAME, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(OrgHost::ID, OrgHost::ID_LENGTH)
                  ->primary();

            $table->char(OrgHost::ORG_ID, Org::ID_LENGTH);

            $table->string(OrgHost::HOSTNAME)
                  ->unique();

            $table->integer(OrgHost::CREATED_AT);

            $table->integer(OrgHost::UPDATED_AT);

            $table->foreign(OrgHost::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG)
                  ->on_delete('restrict');

            $table->index(OrgHost::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ORG_HOSTNAME);
    }
}
