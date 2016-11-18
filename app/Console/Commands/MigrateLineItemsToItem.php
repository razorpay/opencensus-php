<?php

namespace RZP\Console\Commands;

use DB;
use Illuminate\Console\Command;


/**
 * Migrates items related attribute from line_items to items.

 * In detail, does following:
 *  - Move item related data from `line_items` to `items`
 *  - Puts corresponding `items.id` into `line_items.item_id`
 *  - Copy `line_items.invoice_id` into `line_items.entity_id`
 *          and populate `line_items.entity_type` with 'RZP\\Models\\Invoice\\Entity'
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
                        'id'          => $newItemId,
                        'merchant_id' => $lineItem->merchant_id,
                        'name'        => $lineItem->name,
                        'description' => $lineItem->description,
                        'amount'      => $lineItem->amount,
                        'currency'    => $lineItem->currency,
                        'created_at'  => $lineItem->created_at,
                        'updated_at'  => $lineItem->updated_at,
                    ]
                );

                DB::table('line_items')->where('id', $lineItem->id)
                                       ->update(
                                            [
                                                'item_id'     => $newItemId,
                                                'entity_id'   => $lineItem->invoice_id,
                                                'entity_type' => 'RZP\\Models\\Invoice\\Entity',
                                            ]);
            }
        });
    }

}
