<?php

namespace RZP\Console\Commands;

use App;
use Illuminate\Console\Command;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Invoice\Core;

class MerchantInvoiceProcess extends Command
{
    const MODE  = 'mode';

    const YEAR  = 'year';

    const MONTH = 'month';

    protected $app;

    protected $trace;

    protected $mode;

    protected $invoiceDate;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'merchantinvoice:process 
                            {mode            : Database & application mode the command will run in (test|live)}
                            {year            : The Invoice Date creation year}
                            {month           : The Invoice Date creation month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the merchant invoice dispatch in the background.';

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
        $mode  = $this->argument(self::MODE);

        $year  = $this->argument(self::YEAR);

        $month = $this->argument(self::MONTH);

        $this->app = App::getFacadeRoot();

        $this->app['rzp.mode'] = $mode;

        $this->app['trace']->info(
            TraceCode::KUBERNETES_INVOICE_JOB_IN_PROCESS,
            [
                'mode'          => $mode,
                'year'          => $year,
                'month'         => $month
            ]
        );

        (new Core)->processMerchantInvoice($mode, $year, $month);
    }
}