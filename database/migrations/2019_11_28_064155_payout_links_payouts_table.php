<?php

use RZP\Constants\Table;
use RZP\Models\PayoutLink\Entity as PayoutLink;
use RZP\Models\Payout\Entity as Payout;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use RZP\Models\Merchant\Entity as Merchant;
use Illuminate\Database\Migrations\Migration;


class PayoutLinksPayoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payout_links_payouts', function (Blueprint $table) {
            $table->increments('id');

            $table->timestamps();

            $table->char('payout_link_id', PayoutLink::ID_LENGTH);

            $table->char('payout_id', Payout::ID_LENGTH);

            $table->foreign('payout_link_id')
                  ->references(PayoutLink::ID)
                  ->on(Table::PAYOUT_LINK)
                  ->on_delete('restrict');

            $table->foreign('payout_id')
                  ->references(Payout::ID)
                  ->on(Table::PAYOUT)
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
        Schema::dropIfExists('payout_links_payouts');
    }
}
