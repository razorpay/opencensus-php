<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\Escalations\Entity as Escalation;

class CreateMerchantOnboardingEscalations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_ONBOARDING_ESCALATIONS, function (BluePrint $table) {
            $table->engine = 'InnoDB';

            $table->char(Escalation::ID, Escalation::ID_LENGTH)
                ->primary();

            $table->char(Escalation::MERCHANT_ID, Escalation::ID_LENGTH);

            $table->string(Escalation::ESCALATED_TO, 255)
                ->nullable();

            $table->string(Escalation::TYPE, 255)
                ->nullable();

            $table->string(Escalation::MILESTONE, 255)
                ->nullable();

            $table->bigInteger(Escalation::AMOUNT)
                ->unsigned();

            $table->bigInteger(Escalation::THRESHOLD)
                ->unsigned();

            $table->string(Escalation::DESCRIPTION, 255)
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
        Schema::drop(Table::MERCHANT_ONBOARDING_ESCALATIONS);
    }
}
