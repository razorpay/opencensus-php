<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Risk\Entity as Risk;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Merchant\Entity as Merchant;

class CreateRiskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::RISK, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Risk::ID, Risk::ID_LENGTH)
                  ->primary();

            $table->char(Risk::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(Risk::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->string(Risk::FRAUD_TYPE, 30);

            $table->string(Risk::SOURCE, 30);

            // If the risk log is created on confirmation from bank or
            // chargeback or other purposes, we may not get a risk score.
            // hence, it is nullable
            $table->decimal(Risk::RISK_SCORE, 9, 2)
                  ->nullable();

            $table->text(Risk::COMMENTS)
                  ->nullable();

            $table->string(Risk::REASON, 150);

            $table->integer(Risk::CREATED_AT);
            $table->integer(Risk::UPDATED_AT);

            $table->index(Risk::FRAUD_TYPE);
            $table->index(Risk::CREATED_AT);
            $table->index(Risk::RISK_SCORE);
            $table->index([Risk::MERCHANT_ID, Risk::CREATED_AT]);

            $table->foreign(Risk::PAYMENT_ID)
                  ->references(Payment::ID)
                  ->on(Table::PAYMENT);

            $table->foreign(Risk::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT);
        });
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table(Table::RISK, function($table)
        {
            $table->dropForeign(Table::RISK . '_' . Risk::PAYMENT_ID . '_foreign');

            $table->dropForeign(Table::RISK . '_' . Risk::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::RISK);
    }
}
