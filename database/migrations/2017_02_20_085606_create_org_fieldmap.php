<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Org\FieldMap\Entity as FieldMap;

class CreateOrgFieldmap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ORG_FIELDMAP, function (Blueprint $table)
        {
            $table->engine = 'InnoDb';

            $table->char(FieldMap::ID, FieldMap::ID_LENGTH);

            $table->char(FieldMap::ORG_ID, FieldMap::ID_LENGTH);

            $table->string(FieldMap::ENTITY, 250);

            $table->text(FieldMap::FIELDS);

            $table->unique(FieldMap::ORG_ID, FieldMap::ENTITY);

            $table->integer(FieldMap::CREATED_AT);
            $table->integer(FieldMap::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ORG_FIELDMAP);
    }
}
