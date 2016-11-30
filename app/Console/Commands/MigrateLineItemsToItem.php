<?php

namespace RZP\Console\Commands;

use DB;
use Illuminate\Console\Command;
use RZP\Constants\Entity;
use RZP\Models\Item;

/**
 * Migrates items related attribute from line_items to items.

 * In detail, does following:
 *  - Move item related data from `line_items` to `items`
 *  - Puts corresponding `items.id` into `line_items.item_id`
 *  - Copy `line_items.invoice_id` into `line_items.entity_id`
 *          and populate `line_items.entity_type` with 'invoice'
 */
class MigrateLineItemsToItem extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'rzp:migrateLineItemsToItem
                            {--mode=test : Database mode the command will run in (test|live)}
                            {--created_at=0 : Filter line_items with created_at greater than this value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates item related attributes from line_items to items';

    protected $databaseMode;
    protected $createdAt;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->databaseMode = $this->option('mode');
        $this->createdAt    = intval($this->option('created_at'));

        \Database\DefaultConnection::set($this->databaseMode);

        $this->migrateLineItemsToItem();
    }

    protected function migrateLineItemsToItem()
    {
        $lineItems = DB::table('line_items')
                       ->leftJoin('invoices', 'invoices.id', '=', 'line_items.invoice_id')
                       ->select('line_items.*', 'invoices.currency')
                       ->where('line_items.created_at', '>=', $this->createdAt)
                       ->get();

        $this->info(sprintf('Total line_items fetched: %s', count($lineItems)));

        DB::transaction(function() use ($lineItems)
        {
            foreach ($lineItems as $lineItem)
            {
                $newItemId = \RZP\Models\Base\UniqueIdEntity::generateUniqueId();

                DB::table('items')->insert(
                    [
                        Item\Entity::ID          => $newItemId,
                        Item\Entity::MERCHANT_ID => $lineItem->merchant_id,
                        Item\Entity::NAME        => $lineItem->name,
                        Item\Entity::DESCRIPTION => $lineItem->description,
                        Item\Entity::AMOUNT      => $lineItem->amount,
                        Item\Entity::CURRENCY    => $lineItem->currency,
                        Item\Entity::CREATED_AT  => $lineItem->created_at,
                        Item\Entity::UPDATED_AT  => $lineItem->updated_at,
                    ]
                );

                DB::table('line_items')->where('id', $lineItem->id)
                                       ->update(
                                            [
                                                'item_id'     => $newItemId,
                                                'entity_id'   => $lineItem->invoice_id,
                                                'entity_type' => Entity::INVOICE,
                                            ]);
            }
        });
    }
}
