<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\OfflineChallan\Entity as OfflineChallan;
use RZP\Models\OfflineChallan\Status as Status;

class CreateOfflineChallansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::OFFLINE_CHALLAN, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(OfflineChallan::ID, OfflineChallan::ID_LENGTH)
                  ->primary();

            $table->string(OfflineChallan::CHALLAN_NUMBER, OfflineChallan::CHALLAN_LENGTH);

            $table->string(OfflineChallan::STATUS)
                  ->default(Status::PENDING);

            $table->string(OfflineChallan::VIRTUAL_ACCOUNT_ID, OfflineChallan::ID_LENGTH);

            $table->string(OfflineChallan::CLIENT_CODE);

            $table->string(OfflineChallan::BANK_NAME);

            $table->integer(OfflineChallan::CREATED_AT);
            $table->integer(OfflineChallan::UPDATED_AT);

            $table->integer(OfflineChallan::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->index(OfflineChallan::CHALLAN_NUMBER);
            $table->index(OfflineChallan::VIRTUAL_ACCOUNT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::OFFLINE_CHALLAN);
    }
}
