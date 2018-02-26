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

            if (isset($phoneNumbersMap[$email]) === true)
            {
                $request[Customer::CONTACT] =  $phoneNumbersMap[$email];
            } else
            {
                $this->warn('Importing card without phone number for customer ' . $email);
            }


            $customer = $core->createLocalCustomer($request, $merchant, false);

            $card   =   [
                'method'    =>  'card',
                'card'      => [
                    Card::NUMBER        =>  $cardDetails['card_number'],
                    Card::NAME          =>  $cardDetails['name_on_card'],
                    Card::EXPIRY_MONTH  =>  $cardDetails['card_exp_month'],
                    Card::EXPIRY_YEAR   =>  $cardDetails['card_exp_year'],
                ]
            ];

            $tokenCore = new Core();
            $tokenCore->createDirectToken($customer, $card);

            $this->info("Successfully imported card ending with xx" . substr($cardDetails['card_number'], -4) . " for " . $cardDetails['name_on_card']);
            $count++;
        }
        fclose($file);

        $this->info("\n\n\n\nSuccessfully finished importing the cards. Total Cards Imported: " . $count);
    }
}
