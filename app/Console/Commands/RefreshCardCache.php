<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use RZP\Models\Base\UniqueIdEntity;
use Symfony\Component\Console\Input\InputOption;

use RZP\Constants\Mode;
use RZP\Gateway;

class RefreshCardCache extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rzp:refresh_card_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds Card Cache';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        // TODO fix this
        $bladeGateway = Gateway::getGatewayInstance('blade');

        $bladeGateway->setMode(Mode::TEST);

        $bladeGateway->sendCRReq();
    }
}
