<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Org\Hostname\Entity as OrgHostMap;
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

            $table->increments('id');

            $table->char(OrgHostMap::ORG_ID);

            $table->string(OrgHostMap::HOSTNAME)
                  ->unique();

            $table->integer(OrgHostMap::CREATED_AT);

            $table->integer(OrgHostMap::UPDATED_AT);

            $table->integer(OrgHostMap::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->foreign(OrgHostMap::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG)
                  ->on_delete('cascade');
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
