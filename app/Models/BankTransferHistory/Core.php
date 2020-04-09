<?php


namespace RZP\Models\BankTransferHistory;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function backupPayerBankAccount(array $data)
    {
        $bankTransferHistory = new Entity();

        $bankTransferHistory->fill($data);

        $this->repo->saveOrFail($bankTransferHistory);

        return $bankTransferHistory;
    }
}
