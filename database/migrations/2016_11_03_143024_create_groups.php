<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Groups\Entity as Groups;

class CreateGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GROUP, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Groups::ID, 14)
                  ->primary();

            $table->string(Groups::NAME, 250);
            $table->string(Groups::DESCRIPTION, 250);

            $table->char(Groups::ORG_ID, 14);

            $table->integer(Groups::CREATED_AT);
            $table->integer(Groups::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::GROUP);
    }
}
