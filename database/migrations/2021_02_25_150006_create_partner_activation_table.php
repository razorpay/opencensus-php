<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Partner\Activation\Entity as PartnerActivationEntity;


class CreatePartnerActivationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PARTNER_ACTIVATION, function(Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(PartnerActivationEntity::MERCHANT_ID, PartnerActivationEntity::ID_LENGTH)
                  ->primary();;

            $table->string(PartnerActivationEntity::ACTIVATION_STATUS, 30)
                  ->nullable();

            $table->boolean(PartnerActivationEntity::LOCKED)
                  ->default(0);

            $table->boolean(PartnerActivationEntity::SUBMITTED)
                  ->default(0);

            $table->tinyInteger(PartnerActivationEntity::HOLD_FUNDS)
                  ->default(0);

            $table->integer(PartnerActivationEntity::ACTIVATED_AT)
                  ->nullable();

            $table->json(PartnerActivationEntity::KYC_CLARIFICATION_REASONS)
                  ->nullable();

            $table->char(PartnerActivationEntity::REVIEWER_ID, PartnerActivationEntity::ID_LENGTH)
                  ->nullable();

            $table->integer(PartnerActivationEntity::CREATED_AT);

            $table->integer(PartnerActivationEntity::UPDATED_AT);

            $table->integer(PartnerActivationEntity::SUBMITTED_AT)
                  ->nullable();

            $table->index(PartnerActivationEntity::ACTIVATION_STATUS);

            $table->index(PartnerActivationEntity::MERCHANT_ID);

            $table->index(PartnerActivationEntity::REVIEWER_ID);

            $table->index(PartnerActivationEntity::CREATED_AT);

            $table->index(PartnerActivationEntity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PARTNER_ACTIVATION);
    }
}
