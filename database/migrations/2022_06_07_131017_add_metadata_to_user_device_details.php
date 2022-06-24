<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\DeviceDetail\Entity as DeviceDetailsEntity;
use RZP\Constants\Table;

class AddMetadataToUserDeviceDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::USER_DEVICE_DETAIL, function (Blueprint $table) {
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
        Schema::table('user_device_details', function (Blueprint $table) {
            //
        });
    }
}
