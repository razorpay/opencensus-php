<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Constants\Procurer;
use RZP\Models\Terminal\Entity as Terminal;
use RZP\Models\Terminal\Mode;
use RZP\Models\Terminal\Status;
use RZP\Models\Terminal\SyncStatus;
use RZP\Models\Admin\Org\Entity as Org;


class CreateTerminals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TERMINAL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Terminal::ID, Terminal::ID_LENGTH)
                  ->primary();

            $table->char(Terminal::MERCHANT_ID, Terminal::ID_LENGTH);

            $table->char(Terminal::ORG_ID, Terminal::ID_LENGTH)
                  ->default(ORG::RAZORPAY_ORG_ID);

            $table->char(Terminal::PLAN_ID, Terminal::ID_LENGTH)
                  ->nullable();

            $table->string(Terminal::PROCURER)
                  ->default(Procurer::RAZORPAY);

            $table->integer(Terminal::USED_COUNT)
                  ->unsigned()
                  ->default(0);

            $table->tinyInteger(Terminal::USED)
                  ->default(0);

            $table->char(Terminal::CATEGORY, Terminal::CATEGORY_LENGTH)
                  ->nullable();

            $table->string(Terminal::GATEWAY);

            $table->string(Terminal::GATEWAY_MERCHANT_ID)
                  ->nullable();

            $table->string(Terminal::GATEWAY_MERCHANT_ID2)
                  ->nullable();

            $table->string(Terminal::GATEWAY_TERMINAL_ID)
                  ->nullable();

            $table->text(Terminal::GATEWAY_TERMINAL_PASSWORD)
                  ->nullable();

            $table->text(Terminal::GATEWAY_TERMINAL_PASSWORD2)
                  ->nullable();

            $table->string(Terminal::GATEWAY_ACCESS_CODE)
                  ->nullable();

            $table->text(Terminal::GATEWAY_SECURE_SECRET)
                  ->nullable();

            $table->text(Terminal::GATEWAY_SECURE_SECRET2)
                  ->nullable();

            $table->text(Terminal::GATEWAY_RECON_PASSWORD)
                  ->nullable();

            $table->string(Terminal::GATEWAY_ACQUIRER)
                  ->nullable();

            $table->text(Terminal::GATEWAY_CLIENT_CERTIFICATE)
                  ->nullable();

            $table->string(Terminal::MC_MPAN)
                  ->nullable();

            $table->string(Terminal::VISA_MPAN)
                  ->nullable();

            $table->string(Terminal::RUPAY_MPAN)
                  ->nullable();

            $table->string(Terminal::VPA)
                  ->nullable();

            $table->tinyInteger(Terminal::CARD)
                  ->default(0);

            $table->tinyInteger(Terminal::NETBANKING)
                  ->default(0);

            $table->tinyInteger(Terminal::UPI)
                  ->default(0);

            $table->tinyInteger(Terminal::OMNICHANNEL)
                  ->default(0);

            $table->tinyInteger(Terminal::BANK_TRANSFER)
                  ->default(0);

            $table->tinyInteger(Terminal::AEPS)
                  ->default(0);

            $table->tinyInteger(Terminal::EMANDATE)
                  ->default(0);

            $table->tinyInteger(Terminal::NACH)
                  ->default(0);

            $table->tinyInteger(Terminal::EMI)
                  ->default(0);

            $table->tinyInteger(Terminal::CARDLESS_EMI)
                  ->default(0);

            $table->tinyInteger(Terminal::PAYLATER)
                  ->default(0);

            $table->tinyInteger(Terminal::CRED)
                  ->default(0);

            $table->tinyInteger(Terminal::OFFLINE)
                ->default(0);

            $table->tinyInteger(Terminal::APP)
                  ->default(0);

            $table->integer(Terminal::EMI_DURATION)
                  ->nullable();

            $table->string(Terminal::EMI_SUBVENTION)
                  ->nullable();

            $table->tinyInteger(Terminal::RECURRING)
                  ->unsigned()
                  ->default(0);

            $table->tinyInteger(Terminal::CAPABILITY)
                  ->unsigned()
                  ->default(0);

            $table->tinyInteger(Terminal::INTERNATIONAL)
                  ->default(0);

            $table->tinyInteger(Terminal::SHARED)
                   ->default(0);

            $table->tinyInteger(Terminal::TPV)
                  ->default(0);

            $table->integer(Terminal::TYPE)
                  ->unsigned()
                  ->default(1);

            $table->tinyInteger(Terminal::MODE)
                  ->default(Mode::DUAL);

            $table->string(Terminal::STATUS, 255)
                  ->default(Status::ACTIVATED);

            $table->tinyInteger(Terminal::CORPORATE)
                  ->default(0);

            $table->tinyInteger(Terminal::EXPECTED)
                  ->default(0);

            $table->string(Terminal::CURRENCY, 1024)
                  ->default(Terminal::DEFAULT_CURRENCY);

            $table->string(Terminal::NETWORK_CATEGORY)
                  ->nullable();

            $table->json(Terminal::ENABLED_BANKS)
                  ->nullable();

            $table->json(Terminal::ENABLED_APPS)
                  ->nullable();

            $table->json(Terminal::ENABLED_WALLETS)
                  ->nullable();

            $table->string(Terminal::ACCOUNT_NUMBER, 50)
                  ->nullable();

            $table->string(Terminal::IFSC_CODE, 11)
                  ->nullable();

            $table->string(Terminal::VIRTUAL_UPI_ROOT, 10)
                  ->nullable();

            $table->string(Terminal::VIRTUAL_UPI_MERCHANT_PREFIX, 10)
                  ->nullable();

            $table->string(Terminal::VIRTUAL_UPI_HANDLE, 10)
                  ->nullable();

            $table->string(Terminal::ACCOUNT_TYPE, 255)
                  ->nullable();

            $table->text(Terminal::NOTES)
                  ->nullable();

            $table->integer(Terminal::CREATED_AT);

            $table->integer(Terminal::UPDATED_AT);

            $table->integer(Terminal::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->tinyInteger(Terminal::ENABLED)
                  ->default(1);

            $table->tinyInteger(Terminal::SYNC_STATUS)
                  ->default(SyncStatus::getValueForSyncStatusString(SyncStatus::NOT_SYNCED));


            $table->foreign(Terminal::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            // Needed for future
            //$table->integer(Terminal::PRIORITY)
            //      ->default(5);

            $table->index(Terminal::CATEGORY);
            $table->index(Terminal::GATEWAY);
            $table->index(Terminal::DELETED_AT);
            $table->index(Terminal::ENABLED);
            $table->index(Terminal::NETWORK_CATEGORY);
            $table->index(Terminal::GATEWAY_MERCHANT_ID);
            $table->index(Terminal::CREATED_AT);
            $table->index(Terminal::MC_MPAN);
            $table->index(Terminal::VISA_MPAN);
            $table->index(Terminal::RUPAY_MPAN);
            $table->index(Terminal::VPA);
            $table->index(Terminal::CARDLESS_EMI);
            $table->index(Terminal::STATUS);
            $table->index(Terminal::ORG_ID);
            $table->index(Terminal::BANK_TRANSFER);
            $table->index(Terminal::SYNC_STATUS);
            $table->index(Terminal::PLAN_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TERMINAL, function($table)
        {
            $table->dropForeign(
                Table::TERMINAL.'_'.Terminal::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::TERMINAL);
    }
}
