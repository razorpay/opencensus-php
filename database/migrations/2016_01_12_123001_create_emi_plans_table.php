<?php

use RZP\Constants\Table;
use RZP\Models\Emi;
use RZP\Models\Payment\Entity as Payment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmiPlansTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::EMI_PLAN, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Emi\Entity::ID, 14)
                  ->primary();

            $table->char(Emi\Entity::MERCHANT_ID, 14)
                  ->nullable();

            $table->char(Emi\Entity::BANK, 4)
                  ->nullable();

            $table->char(Emi\Entity::NETWORK, 5)
                  ->nullable();

            $table->string(Emi\Entity::COBRANDING_PARTNER, 20)
                ->nullable();

            $table->string(Emi\Entity::TYPE)
                  ->default('credit');

            $table->integer(Emi\Entity::RATE);

            $table->tinyInteger(Emi\Entity::DURATION);

            $table->string(Emi\Entity::METHODS)
                  ->nullable();

            $table->integer(Emi\Entity::MIN_AMOUNT);

            $table->string(Emi\Entity::ISSUER_PLAN_ID)
                  ->nullable();

            $table->string(Emi\Entity::SUBVENTION)
                  ->default(Emi\Subvention::CUSTOMER);

            $table->integer(Emi\Entity::MERCHANT_PAYBACK)
                  ->default(0);

            $table->integer(Emi\Entity::CREATED_AT);

            $table->integer(Emi\Entity::UPDATED_AT);

            $table->integer(Emi\Entity::DELETED_AT)
                  ->nullable();

            $table->index(Emi\Entity::MERCHANT_ID);
            $table->index(Emi\Entity::BANK);
            $table->index(Emi\Entity::NETWORK);
            $table->index(Emi\Entity::SUBVENTION);
            $table->index(Emi\Entity::CREATED_AT);
        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->foreign(Payment::EMI_PLAN_ID)
                  ->references(Emi\Entity::ID)
                  ->on(Table::EMI_PLAN)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::PAYMENT, function($table)
        {
            $table->dropForeign(Table::PAYMENT.'_'.Payment::EMI_PLAN_ID.'_foreign');

        });

        Schema::drop(Table::EMI_PLAN);
    }
}
