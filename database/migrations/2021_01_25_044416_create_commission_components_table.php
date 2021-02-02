<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Partner\Commission;
use RZP\Models\Partner\Commission\Component\Entity as CommissionComponent;

class CreateCommissionComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commission_components', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(CommissionComponent::ID, CommissionComponent::ID_LENGTH)
                  ->primary();

            $table->char(CommissionComponent::COMMISSION_ID, PublicEntity::ID_LENGTH);

            $table->char(CommissionComponent::MERCHANT_PRICING_PLAN_RULE_ID, PublicEntity::ID_LENGTH);

            $table->integer(CommissionComponent::MERCHANT_PRICING_PERCENTAGE)
                  ->unsigned()
                  ->default(0);

            $table->integer(CommissionComponent::MERCHANT_PRICING_FIXED)
                  ->unsigned()
                  ->default(0);

            $table->integer(CommissionComponent::MERCHANT_PRICING_AMOUNT);

            $table->char(CommissionComponent::COMMISSION_PRICING_PLAN_RULE_ID, PublicEntity::ID_LENGTH);

            $table->integer(CommissionComponent::COMMISSION_PRICING_PERCENTAGE)
                  ->unsigned()
                  ->default(0);

            $table->integer(CommissionComponent::COMMISSION_PRICING_FIXED)
                  ->unsigned()
                  ->default(0);

            $table->integer(CommissionComponent::COMMISSION_PRICING_AMOUNT);

            $table->string(CommissionComponent::PRICING_FEATURE, 255);

            $table->string(CommissionComponent::PRICING_TYPE, 255);

            $table->integer(CommissionComponent::CREATED_AT);

            $table->integer(CommissionComponent::UPDATED_AT);

            $table->index(CommissionComponent::COMMISSION_ID);

            $table->index(CommissionComponent::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commission_components');
    }
}
