<?php

namespace Models\Terminal;

use EE\Exception;
use Models\Base;
use Models\Merchant;
use Models\Terminal;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Merchant\Repository();
    }

    public function createTerminal($id, $input)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $terminal = (new Terminal\Core)->create($input, $merchant);

        return $terminal->toArrayPublic();
    }

    public function getTerminals($mid)
    {
        $merchant = $this->repo->findOrFailPublic($mid);

        $terminals = (new Terminal\Repository)->getByMerchantId($mid);

        return $terminals->toArrayPublic();
    }

    public function getTerminal($mid, $tid)
    {
        $merchant = $this->repo->findOrFailPublic($mid);

        $terminal = (new Terminal\Repository)->getByIdAndMerchantId($mid, $id);

        return $terminal->toArrayPublic();
    }

    public function deleteTerminal($mid, $tid)
    {
        $merchant = $this->repo->findOrFailPublic($mid);

        $terminalRepo = new Terminal\Repository;
        $terminal = $terminalRepo->getByIdAndMerchantId($mid, $tid);

        $terminal = $terminalRepo->deleteOrFail($terminal);

        if ($terminal === null)
            return [];

        return $terminal->toArrayPublic();
    }

    public function deleteTerminal2($id)
    {
        $terminalRepo = new Terminal\Repository;

        $terminal = $terminalRepo->findOrFailPublic($id);

        $terminal = $terminalRepo->deleteOrFail($terminal);

        if ($terminal === null)
            return [];

        return $terminal->toArrayPublic();
    }

    public function modifyTerminal($mid, $tid, $input)
    {
        $merchant = $this->repo->findOrFailPublic($mid);

        $terminalRepo = new Terminal\Repository;

        $terminal = $terminalRepo->getByIdAndMerchantId($mid, $tid);

        $terminal = (new Terminal\Core)->edit($terminal, $input);

        return $terminal->toArrayPublic();
    }

    public function editTerminal($tid, $input)
    {
        $terminalRepo = new Terminal\Repository;

        $terminal = $terminalRepo->findOrFail($tid);

        $terminal = (new Terminal\Core)->edit($terminal, $input);

        return $terminal->toArrayPublic();
    }
}
