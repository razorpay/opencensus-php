<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Merchant\Consent\Details\Entity;

class CreateMerchantConsentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_CONSENT_DETAILS, function(Blueprint $table) {

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::URL, 255)->nullable();

            $table->bigInteger(Entity::CREATED_AT);

            $table->bigInteger(Entity::UPDATED_AT);

            $table->index(Entity::URL);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_CONSENT_DETAILS);
    }
}
