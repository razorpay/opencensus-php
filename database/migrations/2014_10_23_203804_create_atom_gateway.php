<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Gateway\Atom\Entity as Atom;

class CreateAtomGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ATOM, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Atom::ID);

            $table->char(Atom::PAYMENT_ID, Atom::ID_LENGTH);

            $table->string(Atom::REFUND_ID, Atom::ID_LENGTH)
                  ->nullable();

            $table->string(Atom::ACTION, 10)
                  ->nullable();

            $table->integer(Atom::RECEIVED)
                  ->default(0);

            $table->string(Atom::AMOUNT, 10);

            $table->string(Atom::STATUS, 5)
                  ->nullable();

            $table->string(Atom::ACCOUNT_NUMBER)
                  ->nullable();

            $table->string(Atom::GATEWAY_PAYMENT_ID)
                  ->nullable();

            $table->char(Atom::TOKEN, 75)
                  ->nullable();

            $table->tinyInteger(Atom::SUCCESS)
                  ->default(0)
                  ->nullable();

            $table->string(Atom::BANK_CODE)
                  ->nullable();

            $table->string(Atom::BANK_NAME)
                  ->nullable();

            $table->string(Atom::BANK_PAYMENT_ID)
                  ->nullable();

            $table->char(Atom::METHOD, 2)
                  ->nullable();

            $table->string(Atom::ERROR_CODE, 5)
                  ->nullable();

            $table->string(Atom::ERROR_DESCRIPTION)
                  ->nullable();

            $table->text(Atom::CALLBACK_DATA)
                  ->nullable();

            $table->integer(Atom::DATE)
                  ->nullable();

            $table->integer(Atom::CREATED_AT);
            $table->integer(Atom::UPDATED_AT);

            $table->foreign(Atom::PAYMENT_ID)
                  ->references(RZP\Models\Payment\Entity::ID)
                  ->on('payments')
                  ->on_delete('restrict');

            $table->foreign(Atom::REFUND_ID)
                  ->references(RZP\Models\Payment\Refund\Entity::ID)
                  ->on('refunds')
                  ->on_delete('restrict');

            $table->index(Atom::GATEWAY_PAYMENT_ID);

            $table->index(Atom::CREATED_AT);

            $table->index(Atom::BANK_PAYMENT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ATOM, function($table)
        {
            $table->dropForeign('atom_payment_id_foreign');
        });

        Schema::drop(Table::ATOM);
    }
}
