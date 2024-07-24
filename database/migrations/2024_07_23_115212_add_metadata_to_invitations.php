<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\DeviceDetail\Entity as DeviceDetailsEntity;
use RZP\Constants\Table;

class AddMetadataToInvitations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::INVITATION, function (Blueprint $table) {
            $table->json(DeviceDetailsEntity::METADATA)
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invitations', function (Blueprint $table) {
            //
        });
    }
}
