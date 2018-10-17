<?php

namespace RZP\Console\Commands;

use App;
use Illuminate\Console\Command;

use RZP\Exception;
use RZP\Models\Customer\Token\Core;
use RZP\Models\Card\Entity as Card;

class ImportCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rzp:import-cards {mode} {merchant} {cards-file} {customers-file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all the cards in a file';

    protected $repo;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $mode = $this->argument("mode");

        $app = App::getFacadeRoot();
        $app['rzp.mode'] = $mode;
        \Database\DefaultConnection::set($mode);

        $this->repo = $app['repo'];

        $merchantId = $this->argument("merchant");
        $customersFilePath   = $this->argument("customers-file");
        $cardsFilePath   = $this->argument("cards-file");

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $fyndUserToCustomerMap = [];

        $file = fopen($customersFilePath,"r");
        fgetcsv($file);

        $this->info("Starting import of customer data");

        while (($row = fgetcsv($file)) !== false)
        {
            $fyndUserId = trim($row[0]);
            $customerId = trim($row[3]);

            $customer = $this->repo->customer->findByPublicId($customerId);

            $fyndUserToCustomerMap[$fyndUserId] = $customer;
        }

        fclose($file);

        $this->info("Total customers to process " . count($fyndUserToCustomerMap));

        $count = 0;
        $file = fopen($cardsFilePath, "r");
        fgetcsv($file);

        while (($row = fgetcsv($file)) !== false)
        {
            $expiry = explode('/', $row[1]);
            $expiryYear = $expiry[0];
            $expiryMonth = $expiry[1];

            $fyndUserId = $row[3];

            $customer = $fyndUserToCustomerMap[$fyndUserId] ?? null;

            if ($customer === null)
            {
                $this->error("No customer found for fyndUserId: $fyndUserId");

                continue;
            }

            $cardDetails   =   [
                'method'    =>  'card',
                'card'      => [
                    Card::NUMBER        =>  $row[0],
                    Card::NAME          =>  $row[2],
                    Card::EXPIRY_MONTH  =>  $expiryMonth,
                    Card::EXPIRY_YEAR   =>  $expiryYear,
                ]
            ];

            $tokenCore = new Core();

            try
            {
                $tokenCore->createDirectToken($customer, $cardDetails);

                $this->info("Successfully imported card ending with xx" . substr($row[0], -4) . " for " . $customer->getPublicId());

                $count++;
            }
            catch (\Throwable $e)
            {
                $this->error("Failed to save card for fynd user: $fyndUserId  rzp customer id: " . $customer->getPublicId());
            }
        }

        fclose($file);

        $this->info("\n\n\n\nSuccessfully finished importing the cards. Total Cards Imported: " . $count);
    }
}
