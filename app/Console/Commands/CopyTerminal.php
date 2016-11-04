<?php

namespace RZP\Console\Commands;

use App;
use Illuminate\Console\Command;
use RZP\Models\Terminal\Entity as Terminal;
use RZP\Models\Merchant\Entity as Merchant;
use Database\DefaultConnection as DefaultDbConn;
use Symfony\Component\Console\Input\InputOption;

class CopyTerminal extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'rzp:copyTerminal
                                {terminalId : Terminal ID to copy}
                                {merchantIds* : Merchant IDs against which new terminal has to be created}
                                {--mode=test : DB mode to use.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy terminals for multiple sub-merchants';

    public function __construct()
    {
        parent::__construct();

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $terminalId = $this->argument('terminalId');

        Terminal::verifyUniqueId($terminalId);

        $merchantIds = $this->argument('merchantIds');

        foreach ($merchantIds as $merchantId)
        {
            Merchant::verifyUniqueId($merchantId);
        }

        $mode = $this->option('mode');

        DefaultDbConn::set($mode);

        $terminal = $this->repo->terminal->findOrFail($terminalId);

        if ($terminal->isShared() === true)
        {
            return $this->error('Shared terminal cannot be copied.');
        }

        $terminalHeaders = ['key', 'value'];
        $terminalRow = [];

        foreach($terminal->toArray() as $key => $value)
        {
            $terminalRow[] = [$key, $value];
        }

        $this->table($terminalHeaders, $terminalRow);

        if ($this->confirm('Do you want to copy the above given terminal?') === false)
        {
            return;
        }

        $tableData = [];

        unset($terminal['used_count']);

        foreach ($merchantIds as $merchantId)
        {
            if (($mode === 'live') and
                ($this->confirm('Do you want to proceed for ' . $merchantId . '?') === false))
            {
                continue;
            }

            $newTerminal = $terminal->replicate();
            $newTerminal['merchant_id'] = $merchantId;

            $this->repo->saveOrFail($newTerminal);

            $tableData[] = [$newTerminal->getId(), $merchantId];
        }

        $headers = ['terminal_id', 'merchant_id'];

        $this->info('New terminals have been created.');

        $this->table($headers, $tableData);
    }
}
