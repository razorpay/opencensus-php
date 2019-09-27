<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\NodalBeneficiary\Entity as NodalBeneficiaries;

class CreateNodalBeneficiaries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::NODAL_BENEFICIARIES, function(Blueprint $table)
        {
            $table->char(NodalBeneficiaries::ID, NodalBeneficiaries::ID_LENGTH)
                  ->primary();

            $table->char(NodalBeneficiaries::MERCHANT_ID, NodalBeneficiaries::ID_LENGTH);

            $table->char(NodalBeneficiaries::BANK_ACCOUNT_ID, NodalBeneficiaries::ID_LENGTH)
                  ->nullable();

            $table->char(NodalBeneficiaries::CARD_ID, NodalBeneficiaries::ID_LENGTH)
                  ->nullable();

            $table->string(NodalBeneficiaries::CHANNEL, 8);

            $table->string(NodalBeneficiaries::BENEFICIARY_CODE, 30)
                  ->nullable();

            $table->string(NodalBeneficiaries::REGISTRATION_STATUS, 40)
                  ->nullable();

            $table->integer(NodalBeneficiaries::CREATED_AT);

            $table->integer(NodalBeneficiaries::UPDATED_AT);

            $table->integer(NodalBeneficiaries::DELETED_AT)
                  ->nullable();

            $table->index(NodalBeneficiaries::CHANNEL);

            $table->index(NodalBeneficiaries::DELETED_AT);

            $table->index(NodalBeneficiaries::CREATED_AT);

            $table->index(NodalBeneficiaries::UPDATED_AT);

            $table->index(NodalBeneficiaries::MERCHANT_ID);

            $table->index(NodalBeneficiaries::CARD_ID);

            $table->index(NodalBeneficiaries::BANK_ACCOUNT_ID);

            $table->index(NodalBeneficiaries::REGISTRATION_STATUS);

            $table->index([NodalBeneficiaries::BANK_ACCOUNT_ID, NodalBeneficiaries::CHANNEL]);

            $table->unique([NodalBeneficiaries::BENEFICIARY_CODE, NodalBeneficiaries::CHANNEL]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::NODAL_BENEFICIARIES);
    }
}
