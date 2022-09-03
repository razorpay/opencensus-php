<?php

use RZP\Constants\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\LinkedAccountReferenceData\Entity;

class CreateLinkedAccountReferenceDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::LINKED_ACCOUNT_REFERENCE_DATA, function (Blueprint $table)

        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->string(Entity::ACCOUNT_NAME, 255)->nullable(false);

            $table->string(Entity::ACCOUNT_EMAIL, 255)->nullable(false);

            $table->string(Entity::BUSINESS_NAME, 255)->nullable(false);

            $table->string(Entity::BUSINESS_TYPE, 255)->nullable(false);

            $table->string(Entity::ACCOUNT_NUMBER, 40)->nullable(false);

            $table->string(Entity::BENEFICIARY_NAME, 120)->nullable(false);

            $table->string(Entity::CATEGORY, 60)->nullable(false);

            $table->tinyInteger(Entity::DASHBOARD_ACCESS)->default(0);

            $table->tinyInteger(Entity::CUSTOMER_REFUND_ACCESS)->default(0);

            $table->tinyInteger(Entity::IS_ACTIVE)->default(1);

            $table->char(Entity::IFSC_CODE, 11)->nullable(false);

            $table->integer(Entity::CREATED_AT)->nullable(false);

            $table->integer(Entity::UPDATED_AT)->nullable();

            $table->index([Entity::CATEGORY]);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::LINKED_ACCOUNT_REFERENCE_DATA);
    }
}
