<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Org\FieldMap\Entity as FieldMap;
use RZP\Models\Admin\Org\Entity as Org;

class CreateOrgFieldMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ORG_FIELD_MAP, function (Blueprint $table)
        {
            $table->engine = 'InnoDb';

            $table->char(FieldMap::ID, FieldMap::ID_LENGTH)
                  ->primary();

            $table->char(FieldMap::ORG_ID, Org::ID_LENGTH);

            $table->string(FieldMap::ENTITY_NAME, 250);

            $table->text(FieldMap::FIELDS);

            $table->unique([FieldMap::ORG_ID, FieldMap::ENTITY_NAME]);

            $table->foreign(FieldMap::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG);

            $table->integer(FieldMap::CREATED_AT);
            $table->integer(FieldMap::UPDATED_AT);
            $table->index(FieldMap::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ORG_FIELD_MAP, function($table)
        {
            $table->dropForeign(
                Table::ORG_FIELD_MAP . '_' . FieldMap::ORG_ID . '_foreign');
        });

        Schema::drop(Table::ORG_FIELD_MAP);
    }
}
