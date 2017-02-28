<?php

namespace RZP\Console\Commands;

use App;
use RZP\Models\Terminal;
use Illuminate\Console\Command;
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

        Terminal\Entity::verifyUniqueId($terminalId);

        $merchantIds = $this->argument('merchantIds');

        foreach ($merchantIds as $merchantId)
        {
            Merchant::verifyUniqueId($merchantId);
        }

        $mode = $this->option('mode');

        DefaultDbConn::set($mode);

        $terminal = $this->repo->terminal->findOrFail($terminalId);

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

        $tableData = (new Terminal\Core)->copy(['merchant_ids' => $merchantIds], $terminal);

        $headers = ['terminal_id', 'merchant_id'];

        $this->info('New terminals have been created.');

        $this->table($headers, $tableData);
    }
}
