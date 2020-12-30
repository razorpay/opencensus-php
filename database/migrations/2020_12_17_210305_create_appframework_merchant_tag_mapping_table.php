<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;

use RZP\Models\Application\ApplicationMerchantTags\Entity as MerchantTag;

class CreateAppframeworkMerchantTagMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::APPLICATION_MERCHANT_TAG, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(MerchantTag::ID, MerchantTag::ID_LENGTH)->primary();

            $table->string(MerchantTag::TAG, 255);

            $table->char(MerchantTag::MERCHANT_ID, MerchantTag::ID_LENGTH);

            $table->integer(MerchantTag::CREATED_AT);

            $table->integer(MerchantTag::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::APPLICATION_MERCHANT_TAG);
    }
}
