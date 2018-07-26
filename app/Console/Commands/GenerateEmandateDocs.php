<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use Razorpay\IFSC\IFSC;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\AuthType;

class GenerateEmandateDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:emandate-banks-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a table to be inserted in eMandate Docs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function displayRow(array $row)
    {
        /**
         * Format is the following:
         *
         * Column 0 | Column 1 | Column 2
         * ---
         */

        echo implode(" | ", $row) . PHP_EOL . "---" . PHP_EOL;
    }

    private function printHeader()
    {
        $this->displayRow([
            "S.No",
            "Bank",
            "IFSC",
            "E-Mandate (Netbanking Authentication)",
            "E-Mandate (Aadhaar OTP Authentication)",
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $aadhaarList = Gateway::getAvailableEmandateBanksForAuthType(AuthType::AADHAAR);
        $netbankingList = Gateway::getAvailableEmandateBanksForAuthType(AuthType::NETBANKING);

        $rows = [];

        foreach ($aadhaarList as $bankCode)
        {
            $rows[$bankCode] = [
                AuthType::AADHAAR => true
            ];
        }

        foreach ($netbankingList as $bankCode)
        {
            if (isset($rows[$bankCode]))
            {
                $rows[$bankCode][AuthType::NETBANKING] = true;
            }
            else
            {
                $rows[$bankCode] = [
                    AuthType::NETBANKING => true
                ];
            }
        }

        $this->printHeader();

        $counter = 1;

        foreach ($rows as $bank=>$data)
        {
            $aadhaar = isset($data[AuthType::AADHAAR]) ? ":white_check_mark:" : "";
            $netbanking = isset($data[AuthType::NETBANKING]) ? ":white_check_mark:" : "";

            $this->displayRow([
                "$counter.",
                substr($bank, 0,4),
                IFSC::getbankName($bank),
                $aadhaar,
                $netbanking
            ]);

            $counter++;
        }
    }
}
