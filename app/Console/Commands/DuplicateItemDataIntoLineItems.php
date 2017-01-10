<?php

namespace RZP\Console\Commands;

use DB;
use Illuminate\Console\Command;
use RZP\Constants\Entity;
use RZP\Models\Item;

class DuplicateItemDataIntoLineItems extends Command
{
    protected $signature = 'rzp:duplicateItemDataIntoLineItems
                            {--mode=test : Database mode the command will run in (test|live)}
                            {--created_at=0 : Filter line_items with created_at greater than this value}';

    protected $description = '';

    protected $databaseMode;

    protected $createdAt;

    public function fire()
    {
        $this->databaseMode = $this->option('mode');
        $this->createdAt    = intval($this->option('created_at'));

        \Database\DefaultConnection::set($this->databaseMode);

        $this->duplicateItemDataIntoLineItems();
    }

    protected function duplicateItemDataIntoLineItems()
    {
        $lineItems = DB::table('line_items')
                       ->leftJoin('items', 'items.id', '=', 'line_items.item_id')
                       ->select(['line_items.id', 'items.name', 'items.description', 'items.amount', 'items.currency'])
                       ->where('line_items.created_at', '>=', $this->createdAt)
                       ->get();

        $this->info(sprintf('Total line_items fetched: %s', count($lineItems)));

        foreach ($lineItems as $lineItem)
        {
            DB::table('line_items')->where('id', $lineItem->id)
                                   ->update(
                                        [
                                            'name'        => $lineItem->name,
                                            'description' => $lineItem->description,
                                            'amount'      => $lineItem->amount,
                                            'currency'    => $lineItem->currency,
                                        ]);
        }
    }
}
