<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Application\ApplicationMerchantMaps\Entity as MerchantMapping;

class CreateAppframeworkMerchantMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::APPLICATION_MERCHANT_MAPPING, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(MerchantMapping::ID, MerchantMapping::ID_LENGTH)->primary();

            $table->char(MerchantMapping::MERCHANT_ID, MerchantMapping::ID_LENGTH);

            $table->char(MerchantMapping::APP_ID, MerchantMapping::ID_LENGTH);

            $table->boolean(MerchantMapping::ENABLED)->default(false);

            $table->integer(MerchantMapping::CREATED_AT);

            $table->integer(MerchantMapping::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::APPLICATION_MERCHANT_MAPPING);
    }
}
