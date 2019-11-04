<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\FundAccount\Validation\Entity as E;

class MakeFavAmountCurrencyNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_account_validations', function (Blueprint $table) {
            $table->bigInteger(E::AMOUNT)
                ->unsigned()
                ->nullable()
                ->change();
        });

        /**
         *
         * Laravel Doctrine doesn't support char type for modification so have used raw sql
         * Refer - https://github.com/laravel/framework/issues/9636
         */
        DB::statement("ALTER TABLE fund_account_validations CHANGE currency currency CHAR(3) NULL;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_account_validations', function (Blueprint $table)
        {
            $table->bigInteger(E::AMOUNT)
                ->unsigned()
                ->change();
        });

        // This will fix any existing null values in database so that next query won't fail
        DB::statement("UPDATE fund_account_validations SET currency = 'INR' WHERE currency = null;");
        DB::statement("ALTER TABLE fund_account_validations CHANGE currency currency CHAR(3) not null;");
    }
}
