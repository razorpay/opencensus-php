<?php

namespace RZP\Console\Commands;

use RZP\Constants\Mode;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use Illuminate\Console\Command;
use RZP\Models\Merchant\Repository;
use Symfony\Component\Console\Input\InputArgument;
use RZP\Models\Card\Entity as Card;

class ImportDirectTokens extends Command
{
    protected static $headers = [
        'rzp_merchant_id',
        'name_on_card',
        'customer_email',
        'customer_phone',
        'card_number',
        'expiry_month',
        'expiry_year',
        'auth_payment_amount',
        'auth_payment_timestamp',
        'auth_payment_arn',
        'auth_payment_gateway',
        'gateway_merchant_id',
        'auth_code',
        'ip_address',
        'notes',
    ];

    protected static $oheaders = [
        'rzp_merchant_id',
        'customer_email',
        'customer_phone',
        'notes (json format)',
        'rzp_customer_id',
        'rzp_token_id',
        'method',
        'card_number (first6, last4)',
        'network',
        'expiry_month',
        'expiry_year',
        'issuer',
        'created_at'
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rzp:import-tokens {merchantIds} {dataFile} {outputFile} {mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all the cards in a file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $merchantIds = (array) array_map('trim', explode(',', $this->argument('merchantIds')));
        $filePath = $this->argument('dataFile');
        $outputFile = $this->argument('outputFile');
        $mode = $this->argument('mode');

        if (Mode::exists($mode) === false)
        {
            $this->error('Mode given is invalid');
            return;
        }

        $app = \App::getFacadeRoot();

        $app['basicauth']->setModeAndDbConnection($mode);
        $trace = $app['trace'];

        $merchantRepository = new Repository();
        $merchants = $merchantRepository->findMany($merchantIds)->keyBy('id');

        $rows = [];
        $count = 0;
        $file = fopen($filePath, 'r');
        $ofile = fopen($outputFile, 'w');

        fputcsv($ofile, self::$oheaders);

        while (feof($file) === false)
        {
            $data = fgetcsv($file);

            if ($data === false)
            {
                break;
            }

            $rows[] = array_combine(self::$headers, $data);
        }

        fclose($file);

        foreach ($rows as $row)
        {
            if ($merchants->has($row['rzp_merchant_id']) === false)
            {
                $this->warn('Merchant Id not found. ' . $row['rzp_merchant_id']);
                continue;
            }

            $customerInfo = [
                Customer\Entity::NAME    => $row['name_on_card'],
                Customer\Entity::EMAIL   => $row['customer_email'],
                Customer\Entity::CONTACT => $row['customer_phone'],
            ];

            $merchant = $merchants[$row['rzp_merchant_id']];

            try
            {
                $customer = (new Customer\Core)->createLocalCustomer($customerInfo, $merchant, false);
            }
            catch(\Exception $e)
            {
                $this->error('Failed to create customer for ' . $customerInfo['email'] . PHP_EOL . 'Error: ' . $e->getMessage());
                continue;
            }

            $cardInfo = [
                'method'    => 'card',
                'card'      => [
                    Card::NUMBER       => $row['card_number'],
                    Card::NAME         => $customer->getName(),
                    Card::EXPIRY_MONTH => $row['expiry_month'],
                    Card::EXPIRY_YEAR  => $row['expiry_year'],
                ]
            ];

            $tokenCore = new Token\Core();

            try
            {
                $token = $tokenCore->createDirectToken($customer, $cardInfo);

                $this->info('Successfully imported card ending with ' . substr($row['card_number'], -4) . ' for ' . $customer->getId());

                $count++;
            }
            catch (\Exception $e)
            {
                $this->error('Failed to save card for ' . $customer->getId() . PHP_EOL . 'Error: ' . $e->getMessage());
                continue;
            }

            $output = [
                $row['rzp_merchant_id'],
                $customer->getEmail(),
                $customer->getContact(),
                $row['notes'],
                $customer->getPublicId(),
                $token->getPublicId(),
                'card',
                $token->card->getIin() . 'xxxx' . $token->card->getLast4(),
                $token->card->getNetwork(),
                $token->card->getExpiryMonth(),
                $token->card->getExpiryYear(),
                $token->card->getIssuer(),
                $token->getCreatedAt(),
            ];

            fputcsv($ofile, $output);

            $traceData = [
                'merchant_id'    => $row['rzp_merchant_id'],
                'customer_email' => $customer->getEmail(),
                'customer_phone' => $customer->getContact(),
                'card'           => $token->card->getIin() . 'xxxx' . $token->card->getLast4(),
                'token_id'       => $token->getId(),
                'customer_id'    => $customer->getId(),
                'notes'          => $row['notes'],
                'payment_data'   => [
                    'arn'       => $row['auth_payment_arn'],
                    'ts'        => $row['auth_payment_timestamp'],
                    'amount'    => $row['auth_payment_amount'],
                    'gateway'   => $row['auth_payment_gateway'],
                    'mid'       => $row['gateway_merchant_id'],
                    'auth_code' => $row['auth_code'],
                    'ip'        => $row['ip_address'],
                ]
            ];

            $trace->info(TraceCode::CUSTOMER_DIRECT_TOKEN_CREATE, $traceData);
        }

        fclose($ofile);

        $this->info("\n\n\n\nSuccessfully finished importing the cards. Total Cards Imported: " . $count);
    }
}
