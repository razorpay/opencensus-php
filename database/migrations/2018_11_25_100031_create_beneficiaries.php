<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Beneficiary\Entity as Beneficiary;
use RZP\Models\Merchant\Entity as Merchant;

class CreateBeneficiaries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BENEFICIARY, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Beneficiary::ID, 14)
                  ->primary();

            $table->char(Beneficiary::MERCHANT_ID, 14);

            $table->string(Beneficiary::NAME, 50)
                  ->nullable();

            $table->string(Beneficiary::CONTACT, 20)
                  ->nullable();

            $table->string(Beneficiary::EMAIL, 255)
                  ->nullable();

            $table->text(Beneficiary::NOTES);

            $table->tinyInteger(Beneficiary::ACTIVE)
                  ->default(1);

            $table->integer(Beneficiary::CREATED_AT);

            $table->integer(Beneficiary::UPDATED_AT);

            $table->integer(Beneficiary::DELETED_AT)
                  ->nullable();

            $table->index(Beneficiary::EMAIL);

            $table->index([Beneficiary::CONTACT, Beneficiary::EMAIL, Beneficiary::MERCHANT_ID]);

            $table->index([Beneficiary::CONTACT, Beneficiary::MERCHANT_ID]);

            $table->index([Beneficiary::MERCHANT_ID, Beneficiary::CREATED_AT]);

            $table->index(Beneficiary::DELETED_AT);

            // TODO: Uncomment after merge
            //$table->foreign(Beneficiary::MERCHANT_ID)
            //      ->references(Merchant::ID)
            //      ->on(Table::MERCHANT)
            //      ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::table(Table::BENEFICIARY, function($table)
        //{
        //    $table->dropForeign(Table::BENEFICIARY . '_' . Beneficiary::MERCHANT_ID . '_foreign');
        //});

        Schema::drop(Table::BENEFICIARY);
    }
}
