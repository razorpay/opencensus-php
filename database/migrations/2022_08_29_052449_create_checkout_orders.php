<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Checkout\Order\Entity as CheckoutOrder;
use RZP\Models\Checkout\Order\Status as CheckoutOrderStatus;

class CreateCheckoutOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CHECKOUT_ORDER, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(CheckoutOrder::ID, CheckoutOrder::ID_LENGTH);

            $table->char(CheckoutOrder::MERCHANT_ID, CheckoutOrder::ID_LENGTH);

            $table->string(CheckoutOrder::CHECKOUT_ID, CheckoutOrder::ID_LENGTH)
                ->nullable();

            $table->char(CheckoutOrder::ORDER_ID, CheckoutOrder::ID_LENGTH)
                ->nullable();

            $table->char(CheckoutOrder::INVOICE_ID, CheckoutOrder::ID_LENGTH)
                ->nullable();

            $table->string(CheckoutOrder::CONTACT, 20)
                ->nullable();

            $table->string(CheckoutOrder::EMAIL, 255)
                ->nullable();

            $table->json(CheckoutOrder::META_DATA);

            $table->string(CheckoutOrder::STATUS, 16)
                ->default(CheckoutOrderStatus::ACTIVE);

            $table->integer(CheckoutOrder::EXPIRE_AT)
                ->nullable();

            $table->string(CheckoutOrder::CLOSE_REASON, 50)
                ->nullable();

            $table->integer(CheckoutOrder::CLOSED_AT)
                ->nullable();

            $table->integer(CheckoutOrder::CREATED_AT);

            $table->integer(CheckoutOrder::UPDATED_AT);

            $table->primary([CheckoutOrder::ID, CheckoutOrder::CREATED_AT]);

            $table->index(CheckoutOrder::CREATED_AT);

            $table->index(CheckoutOrder::MERCHANT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::CHECKOUT_ORDER);
    }
}
