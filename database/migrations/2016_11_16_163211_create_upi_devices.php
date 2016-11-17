<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Upi\Device\Entity as Device;
use RZP\Models\Customer\Entity as Customer;

class CreateUpiDevices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::UPI_DEVICE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Device::ID, Device::ID_LENGTH)
                  ->primary();

            $table->char(Device::CUSTOMER_ID, Device::ID_LENGTH);

            $table->char(Device::DEVICE_TOKEN, Device::ID_LENGTH);

            $table->string(Device::MOBILE);

            $table->string(Device::GEOCODE)
                  ->nullable();

            $table->string(Device::LOCATION)
                  ->nullable();

            $table->string(Device::IP)
                  ->nullable();

            $table->string(Device::TYPE)
                  ->nullable();

            $table->string(Device::OS)
                  ->nullable();

            $table->string(Device::APP)
                  ->nullable();

            $table->string(Device::CAPABILITY);

            $table->tinyInteger(Device::VERIFIED)
                  ->default(0);

            $table->integer(Device::CREATED_AT);
            $table->integer(Device::UPDATED_AT);

            $table->index(Device::CUSTOMER_ID);

            $table->foreign(Device::CUSTOMER_ID)
                  ->references(Customer::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::UPI_DEVICE, function($table)
        {
            $table->dropForeign(Table::UPI_DEVICE.'_'.Device::CUSTOMER_ID.'_foreign');
        });

        Schema::drop(Table::UPI_DEVICE);
    }
}
