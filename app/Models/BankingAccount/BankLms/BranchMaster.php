<?php

namespace RZP\Models\BankingAccount\BankLms;

class BranchMaster
{
    protected $branchesData = [];
    protected $branchMasterFilePath = __DIR__ . '/branchMasterData.json';

    public function __construct()
    {
        $file = file_get_contents($this->branchMasterFilePath);
        $this->branchesData = json_decode($file, true);
    }

    public function getBranches(): array
    {
        return $this->branchesData;
    }

    public function getBranchByBranchCode($branchCode): ?array
    {

        $branchesList = $this->branchesData;
        foreach ($branchesList as $key => $value) {
            if ($value['branch_code'] == $branchCode) {
                return $value;
            }
        }
        return null;
    }

}
