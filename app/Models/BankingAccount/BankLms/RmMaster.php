<?php

namespace RZP\Models\BankingAccount\BankLms;

class RmMaster
{
    protected $rmData = [];
    protected $rmMasterFilePath = __DIR__ . '/branchRmData.json';

    public function __construct()
    {
        $file = file_get_contents($this->rmMasterFilePath);
        $this->rmData = json_decode($file, true);
    }

    public function getRms(): array
    {
        return $this->rmData;
    }

    public function getRMByEmployeeCode($employeeCode): ?array
    {
        $branchesList = $this->rmData;
        foreach ($branchesList as $key => $value) {
            if ($value['employee_code'] == $employeeCode) {
                return $value;
            }
        }
        return null;
    }
}
