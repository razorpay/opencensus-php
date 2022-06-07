<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\User\Entity as User;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\DeviceDetail\Entity as DeviceDetail;

class CreateUserDeviceDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::USER_DEVICE_DETAIL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(DeviceDetail::ID, DeviceDetail::ID_LENGTH)
                ->primary();

            $table->char(Merchant::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(User::USER_ID, User::ID_LENGTH);

            $table->string(DeviceDetail::APPSFLYER_ID, 50)
                ->nullable();

            $table->string(DeviceDetail::SIGNUP_SOURCE, 50)
                ->nullable();

            $table->string(DeviceDetail::SIGNUP_CAMPAIGN, 50)
                ->nullable();

            $table->integer(DeviceDetail::CREATED_AT);

            $table->integer(DeviceDetail::UPDATED_AT);

            $table->index(Merchant::MERCHANT_ID);

            $table->index(User::USER_ID);

            $table->index(DeviceDetail::CREATED_AT);

            $table->index(DeviceDetail::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::USER_DEVICE_DETAIL);
    }
}
