<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Ecollect\Entity as Ecollect;
use RZP\Constants\Table;

class CreateEcollect extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ECOLLECT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Ecollect::ID, Ecollect::ID_LENGTH)
                  ->primary();

            $table->char(Ecollect::PAYMENT_ID, Ecollect::ID_LENGTH)
                  ->nullable();

            $table->string(Ecollect::PAYER_ACCOUNT, 20);

            $table->string(Ecollect::PAYER_IFSC, 11);

            $table->string(Ecollect::PAYEE_ACCOUNT, 20);

            $table->string(Ecollect::PAYEE_IFSC, 11);

            $table->integer(Ecollect::AMOUNT);

            $table->string(Ecollect::MODE, 5);

            $table->string(Ecollect::TRANSACTION_ID, 30);

            $table->integer(Ecollect::TIME);

            $table->text(Ecollect::DESCRIPTION)
                  ->nullable();

            $table->integer(Ecollect::CREATED_AT);
            $table->integer(Ecollect::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::Ecollect);
    }
}
