<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\NodalBeneficiaries\Entity as NodalBeneficiaries;

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

            $table->char(NodalBeneficiaries::BANK_ACCOUNT_ID, NodalBeneficiaries::ID_LENGTH);

            $table->string(NodalBeneficiaries::NODAL_BANK, 8);

            $table->string(NodalBeneficiaries::BENEFICIARY_CODE, 30)
                  ->nullable()
                  ->unique();

            $table->string(NodalBeneficiaries::STATUS, 40)
                  ->nullable();

            $table->integer(NodalBeneficiaries::DELETED_AT)
                  ->nullable();

            $table->integer(NodalBeneficiaries::CREATED_AT);

            $table->integer(NodalBeneficiaries::UPDATED_AT);

            $table->index(NodalBeneficiaries::STATUS);

            $table->index(NodalBeneficiaries::DELETED_AT);

            $table->index(NodalBeneficiaries::CREATED_AT);

            $table->index(NodalBeneficiaries::UPDATED_AT);

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
