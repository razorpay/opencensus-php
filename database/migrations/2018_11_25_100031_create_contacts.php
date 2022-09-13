<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Batch\Entity as Batch;
use RZP\Models\Contact\Entity as Contact;
use RZP\Models\Merchant\Entity as Merchant;

class CreateContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CONTACT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Contact::ID, Contact::ID_LENGTH)
                  ->primary();

            $table->char(Contact::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(Contact::BATCH_ID, Batch::ID_LENGTH)
                  ->nullable();

            $table->char(Contact::IDEMPOTENCY_KEY, Batch::IDEMPOTENCY_ID_LENGTH)
                  ->nullable();

            $table->string(Contact::NAME, 255)
                  ->nullable();

            $table->string(Contact::CONTACT, 255)
                  ->nullable();

            $table->string(Contact::EMAIL, 255)
                  ->nullable();

            $table->string(Contact::TYPE, 255)
                  ->nullable();

            $table->string(Contact::REFERENCE_ID, 255)
                  ->nullable();

            $table->text(Contact::NOTES);

            $table->tinyInteger(Contact::ACTIVE)
                  ->default(1);

            $table->char(Contact::GST_IN, 15)
                  ->nullable();

            $table->integer(Contact::CREATED_AT);

            $table->integer(Contact::UPDATED_AT);

            $table->integer(Contact::DELETED_AT)
                  ->nullable();

            $table->index(Contact::EMAIL);

            $table->index([Contact::CONTACT, Contact::EMAIL, Contact::MERCHANT_ID]);

            $table->index([Contact::NAME, Contact::MERCHANT_ID]);

            $table->index([Contact::CONTACT, Contact::MERCHANT_ID]);

            $table->index([Contact::TYPE, Contact::MERCHANT_ID]);

            $table->index([Contact::REFERENCE_ID, Contact::MERCHANT_ID]);

            $table->index([Contact::MERCHANT_ID, Contact::ACTIVE]);

            $table->index([Contact::MERCHANT_ID, Contact::CREATED_AT]);

            $table->index(Contact::DELETED_AT);

            $table->foreign(Contact::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
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
        Schema::table(Table::CONTACT, function($table)
        {
           $table->dropForeign(Table::CONTACT . '_' . Contact::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::CONTACT);
    }
}
