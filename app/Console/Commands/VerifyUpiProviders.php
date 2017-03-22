<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Gateway\Upi\Base\ProviderCode;

class VerifyUpiProviders extends Command
{
    // The cannonical URL is bit.ly/UPIApps
    // and this is the published CSV export of the first worksheet
    const SPREADSHEET_URL = 'https://goo.gl/AqFY8Y';

    const PSP_REGEX = '/@([a-z]+)/';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upi:verify_providers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify UPI Provider codes against an online listing';

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
        $csv = array_map('str_getcsv', file(self::SPREADSHEET_URL));

        $psps = [];

        // See http://www.rubular.com/r/cCMuz21dlX for regex
        foreach ($csv as $row)
        {
            $vpa = $row[7];

            $matches = null;

            if (preg_match_all(self::PSP_REGEX, $vpa, $matches) >= 0)
            {
                $validProviders = $matches[1];

                $psps = array_merge($psps, $validProviders);
            }
        }

        $psps = array_unique($psps);

        foreach ($psps as $provider)
        {
            if (!ProviderCode::validate($provider))
            {
                $this->error("Provider missing: @" . $provider);
            }
        }
    }
}
