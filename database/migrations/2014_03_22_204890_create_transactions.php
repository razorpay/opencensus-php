<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement;
use RZP\Models\Transaction\Entity as Transaction;
use RZP\Models\Transaction\CreditType;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Merchant\FeeModel;


class CreateTransactions extends Migration
{

    /**
     * Runs the migration
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TRANSACTION, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Transaction::ID, Transaction::ID_LENGTH)
                  ->primary();

            $table->char(Transaction::ENTITY_ID, Transaction::ID_LENGTH);

            $table->string(Transaction::TYPE, 255);

            $table->char(Transaction::MERCHANT_ID, Transaction::ID_LENGTH);

            $table->bigInteger(Transaction::AMOUNT)
                  ->unsigned();

            $table->integer(Transaction::FEE);

            $table->integer(Transaction::MDR)
                  ->unsigned()
                  ->nullable();

            $table->integer(Transaction::TAX)
                  ->nullable();

            $table->char(Transaction::PRICING_RULE_ID, Transaction::ID_LENGTH)
                  ->nullable();

            $table->bigInteger(Transaction::DEBIT)
                  ->unsigned();

            $table->bigInteger(Transaction::CREDIT)
                  ->unsigned();

            $table->char(Transaction::CURRENCY, 3);

            $table->bigInteger(Transaction::BALANCE)
                  ->nullable();

            $table->integer(Transaction::GATEWAY_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->integer(Transaction::GATEWAY_FEE)
                  ->unsigned()
                  ->nullable();

            $table->integer(Transaction::GATEWAY_SERVICE_TAX)
                  ->unsigned()
                  ->nullable();

            $table->integer(Transaction::API_FEE)
                  ->nullable();

            $table->tinyInteger(Transaction::GRATIS)
                  ->default(0);

            $table->bigInteger(Transaction::CREDITS)
                  ->default(0);

            $table->bigInteger(Transaction::ESCROW_BALANCE)
                  ->nullable();

            $table->string(Transaction::CHANNEL, 8);

            $table->tinyInteger(Transaction::FEE_BEARER)
                  ->default(FeeBearer::getValueForBearerString(FeeBearer::NA));

            $table->tinyInteger(Transaction::FEE_MODEL)
                  ->default(FeeModel::getValueForFeeModelString(FeeModel::NA));

            $table->string(Transaction::CREDIT_TYPE, 25)
                  ->default(CreditType::DEFAULT);

            $table->integer(Transaction::ON_HOLD)
                  ->default(0);

            $table->tinyInteger(Transaction::SETTLED)
                  ->default(0);

            $table->integer(Transaction::SETTLED_AT)
                  ->nullable();

            $table->integer(Transaction::GATEWAY_SETTLED_AT)
                ->nullable();

            // This is a foreign key. The foreign key part is defined
            // in Settlement migration file
            $table->char(Transaction::SETTLEMENT_ID, Transaction::ID_LENGTH)
                  ->nullable();

            $table->integer(Transaction::RECONCILED_AT)
                  ->nullable();

            $table->string(Transaction::RECONCILED_TYPE)
                  ->nullable();

            $table->char(Transaction::BALANCE_ID, Transaction::ID_LENGTH)
                  ->nullable();

            $table->char(Transaction::REFERENCE3, Transaction::ID_LENGTH)
                  ->nullable();

            $table->char(Transaction::REFERENCE4, Transaction::ID_LENGTH)
                  ->nullable();

            $table->tinyInteger(Transaction::BALANCE_UPDATED)
                  ->nullable();

            $table->tinyInteger(Transaction::REFERENCE6)
                  ->nullable();

            $table->bigInteger(Transaction::CUSTOMER_FEE)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(Transaction::CUSTOMER_TAX)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(Transaction::REFERENCE9)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(Transaction::POSTED_AT)
                  ->unsigned()
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Transaction::CREATED_AT);
            $table->integer(Transaction::UPDATED_AT);

            $table->index(Transaction::ENTITY_ID);

            $table->index(Transaction::TYPE);

            $table->index(Transaction::ON_HOLD);

            $table->index(Transaction::RECONCILED_AT);

            $table->index(Transaction::GATEWAY_SETTLED_AT);

            $table->index(Transaction::CHANNEL);

            $table->index(Transaction::GRATIS);

            $table->index(Transaction::DEBIT);

            $table->index(Transaction::CREDIT);

            $table->index(Transaction::CREATED_AT);

            $table->index(Transaction::UPDATED_AT);

            $table->index([Transaction::MERCHANT_ID, Transaction::CREATED_AT]);

            $table->index([Transaction::BALANCE_ID, Transaction::POSTED_AT]);

            $table->index([Transaction::SETTLED, Transaction::CHANNEL, Transaction::ON_HOLD, Transaction::MERCHANT_ID]);

            $table->index([Transaction::SETTLED_AT, Transaction::MERCHANT_ID]);

            $table->foreign(Transaction::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TRANSACTION, function($table)
        {
            $table->dropForeign(
                Table::TRANSACTION.'_'.Transaction::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::TRANSACTION);
    }
}
