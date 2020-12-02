<?php

use RZP\Constants\Table;
use RZP\Models\Merchant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\AutoKyc\Escalations\Entity as Escalation;

class CreateMerchantAutoKycEscalations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_AUTO_KYC_ESCALATIONS, function (BluePrint $table) {
            $table->engine = 'InnoDB';

            $table->char(Escalation::ID, Escalation::ID_LENGTH)
                ->primary();

            $table->char(Escalation::MERCHANT_ID, Escalation::ID_LENGTH);

            $table->char(Escalation::WORKFLOW_ID, Escalation::ID_LENGTH)
                ->nullable();

            $table->tinyInteger(Escalation::ESCALATION_LEVEL)
                ->nullable();

            $table->string(Escalation::ESCALATION_METHOD, 255)
                ->nullable();

            $table->string(Escalation::ESCALATION_TYPE, 255)
                ->nullable();

            $table->integer(Escalation::CREATED_AT);

            $table->integer(Escalation::UPDATED_AT);

            $table->index(Escalation::CREATED_AT);
            $table->index(Escalation::MERCHANT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MERCHANT_AUTO_KYC_ESCALATIONS);
    }
}
