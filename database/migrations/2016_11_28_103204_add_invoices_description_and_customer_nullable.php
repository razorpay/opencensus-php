<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Invoice\Entity as Invoice;
use RZP\Constants\Table;

/**
 * - Adds invoices.description column
 * - Makes invoices.customer_id nullable
 */
class AddInvoicesDescriptionAndCustomerNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::INVOICE, function ($table) {

            $table->text(Invoice::DESCRIPTION)
                  ->nullable()
                  ->after(Invoice::CUSTOMER_CONTACT);

            $table->string(Invoice::CUSTOMER_ID, Invoice::ID_LENGTH)
                  ->nullable()
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::INVOICE, function ($table) {

            $table->dropColumn(Invoice::DESCRIPTION);

            $table->string(Invoice::CUSTOMER_ID, Invoice::ID_LENGTH)
                  ->change();
        });
    }
}
