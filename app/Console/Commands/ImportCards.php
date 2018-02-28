<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Models\Customer\Token\Core;
use RZP\Models\Merchant\Repository;
use Symfony\Component\Console\Input\InputArgument;
use RZP\Models\Customer\Entity as Customer;
use RZP\Models\Card\Entity as Card;

class ImportCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rzp:import-cards {merchant} {cards-file} {phone-numbers-file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all the cards in a file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        $merchantId = $this->argument("merchant");
        $phoneNumbersFilePath   = $this->argument("phone-numbers-file");
        $cardsFilePath   = $this->argument("cards-file");

        $merchantRepository = new Repository();
        $merchant = $merchantRepository->findOrFail($merchantId);

        $core = new \RZP\Models\Customer\Core();

        $phoneNumbersMap = [];

        $file = fopen($phoneNumbersFilePath,"r");
        while (feof($file) === false)
        {
            $row = fgetcsv($file);

            if ($row === false) break;

            $email = trim($row[1]);
            $phone = trim($row[2]);

            $phoneNumbersMap[$email] = $phone;
        }
        fclose($file);


        $count = 0;
        $file = fopen($cardsFilePath,"r");
        while (feof($file) === false)
        {
            $jsonString = fgets($file);

            if ($jsonString === false) break;

            $cardDetails = json_decode($jsonString, true);
            $email = $cardDetails['customer_id'];

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
            {
                $this->error("Ignored customer_id " . $email);
                continue;
            }

            $request = [
                Customer::NAME      =>  $cardDetails['name_on_card'],
                Customer::EMAIL     =>  $cardDetails['customer_id'],
            ];

            if (empty($request[Customer::NAME]) === true)
            {
                $request[Customer::NAME] = "Name";
            }

            if (isset($phoneNumbersMap[$email]) === true)
            {
                $request[Customer::CONTACT] =  $phoneNumbersMap[$email];
            } else
            {
                $this->warn('Importing card without phone number for customer ' . $email);
            }

            try
            {
                $customer = $core->createLocalCustomer($request, $merchant, false);
            }
            catch(\Exception $e)
            {
                $this->error("Failed to create customer for " . $cardDetails['customer_id']);
                continue;
            }

            $card   =   [
                'method'    =>  'card',
                'card'      => [
                    Card::NUMBER        =>  $cardDetails['card_number'],
                    Card::NAME          =>  $request[Customer::NAME],
                    Card::EXPIRY_MONTH  =>  $cardDetails['card_exp_month'],
                    Card::EXPIRY_YEAR   =>  $cardDetails['card_exp_year'],
                ]
            ];

            $tokenCore = new Core();
            try
            {
                $tokenCore->createDirectToken($customer, $card);
                $this->info("Successfully imported card ending with xx" . substr($cardDetails['card_number'], -4) . " for " . $cardDetails['customer_id']);
                $count++;
            }
            catch (\Exception $e)
            {
                $this->error("Failed to save card for " . $cardDetails['customer_id']);
            }

        }
        fclose($file);

        $this->info("\n\n\n\nSuccessfully finished importing the cards. Total Cards Imported: " . $count);
    }
}
