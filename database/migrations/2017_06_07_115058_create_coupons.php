<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Coupon\Entity as Coupon;
use RZP\Models\Payment\Entity as Payment;

class CreateCoupons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::COUPON, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Coupon::ID, Coupon::ID_LENGTH)
                  ->primary();

            $table->char(Coupon::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(Coupon::ENTITY_ID, Coupon::ID_LENGTH);

            $table->string(Coupon::ENTITY_TYPE, 20);

            $table->string(Coupon::CODE, 10)
                  ->unique();

            $table->integer(Coupon::START_AT)
                  ->nullable();

            $table->integer(Coupon::END_AT)
                  ->nullable();

            $table->integer(Coupon::MAX_COUNT)
                  ->nullable()
                  ->unsigned();

            $table->integer(Coupon::USED_COUNT)
                  ->default(0)
                  ->unsigned();

            $table->integer(Coupon::CREATED_AT);

            $table->integer(Coupon::UPDATED_AT);

            $table->integer(Coupon::DELETED_AT)
                  ->nullable();

            $table->index(Coupon::ENTITY_ID);

            $table->index(Coupon::ENTITY_TYPE);

            $table->foreign(Coupon::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index(Coupon::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::COUPON, function($table)
        {
            $table->dropForeign(Table::COUPON.'_'.Coupon::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::COUPON);
    }
}
