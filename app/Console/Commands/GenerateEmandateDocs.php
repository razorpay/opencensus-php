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

        echo implode(" | ", $row) . PHP_EOL . '---' . PHP_EOL;
    }

    private function printHeader()
    {
        $this->displayRow([
            "S.No",
            "Bank",
        ]);
    }

    /**
     *
     */
    private function displayBanks(string $header, array $bankCodes)
    {
        echo "### $header\n";
        echo "<table>\n";
        $counter = 1;

        $this->printHeader();

        foreach ($bankCodes as $bankCode)
        {
            $this->displayRow([
                "$counter",
                IFSC::getBankName(substr($bankCode, 0, 4))
            ]);

            $counter++;
        }
        echo "</table>\n";
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

        $this->displayBanks("Netbanking Authentication", $netbankingList);
        $this->displayBanks("Aadhaar OTP Authentication", $aadhaarList);
    }
}
