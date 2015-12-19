<?php

namespace Models\Merchant\BankAccount;

use Models\Base;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository;
    }

    /**
     * generate the benificiary code from benificiary name
     *
     * @param  string $name benificiary name
     * @param  string $mode the mode that should be used
     * @return string       generated benificiary code
     */
    public function generateBenificiaryCode($name, $mode)
    {
        // Caps all then remove spaces then cut first 4.
        $code = substr(str_replace(' ', '', strtoupper($name)), 0, 4);

        $count = $this->repo->getBeneficiaryCodeCountByPattern($code, $mode);

        if ($count === 0)
            $count = '';
        else
            $count++;

        $code .= $count;

        return $code;
    }
}
