<?php

namespace RZP\Models\Terminal\Onboarding;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\Core as TerminalCore;

class Service extends Base\Service
{
    protected $core;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function enableTerminal(string $id)
    {
        $merchantId = $this->merchant->getId();

        $this->trace->info(
            TraceCode::TERMINAL_ENABLE_REQUEST,
            [
                'merchant_id' => $merchantId,
                'terminal_id' => $id
            ]);

        $terminal = $this->repo->terminal->findByIdAndMerchantId($id, $merchantId);

        $terminal = (new TerminalCore)->toggle($terminal, true);

        return $terminal->toArrayPublic();
    }

    public function disableTerminal(string $id)
    {
        $merchantId = $this->merchant->getId();

        $this->trace->info(
            TraceCode::TERMINAL_DISABLE_REQUEST,
            [
                'merchant_id' => $merchantId,
                'terminal_id' => $id
            ]);

        $terminal = $this->repo->terminal->findByIdAndMerchantId($id, $merchantId);

        $terminal = (new TerminalCore)->toggle($terminal, false);
        
        return $terminal->toArrayPublic();
    }

    public function fetchTerminals(array $input)
    {
        $merchantId = $this->merchant->getId();

        $terminals = $this->repo->terminal->fetch($input, $merchantId);

        return $terminals->toArrayPublic();
    }
}
