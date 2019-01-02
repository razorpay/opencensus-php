<?php

namespace RZP\Models\P2p\Base\Upi;

use RZP\Models\P2p\Device;
use RZP\Models\P2p\BankAccount;
use RZP\Models\P2p\Base\Libraries\Rules;

class ClientLibrary
{
    const CL                = 'cl';
    const CAPABILITY        = 'capability';
    const CHALLENGE         = 'challenge';
    const TOKEN             = 'token';
    const PAYLOAD           = 'payload';
    const FORMAT            = 'format';

    /**
     * @var BankAccount\Entity
     */
    protected $bankAccount;

    /**
     * @var Device\Entity
     */
    protected $device;

    /**
     * @var Txn
     */
    protected $txn;

    public static function rules(): Rules
    {
        return new Rules([
            self::CAPABILITY    => 'string',
            self::CHALLENGE     => 'string',
            self::TOKEN         => 'string',
            self::PAYLOAD       => 'string',
            self::FORMAT        => 'string',
        ]);
    }

    public function setBankAccount(BankAccount\Entity $bankAccount)
    {
        $this->bankAccount = $bankAccount;
    }

    public function setDevice(Device\Entity $device)
    {
        $this->device = $device;
    }

    public function setTxn(Txn $txn)
    {
        $this->txn = $txn;
    }

    public function toArrayPublic()
    {
        $array = [
            'mobileNumber'        => $this->device->getContact(),
            'appId'               => $this->device->getAppName(),
            'deviceId'            => $this->device->getId(),
        ];

        if (is_null($this->txn) === false)
        {
            $this->setTxnProperties($array);
        }

        if (is_null($this->bankAccount) === false)
        {
            $this->setBankAccountProperties($array);
        }

        return $array;
    }

    private function setBankAccountProperties(array & $array)
    {
        $array['account']             = $this->bankAccount->getMaskedAccountNumber();
        $array['registration_format'] = $this->bankAccount->parentBank->getUpiFormat();

        foreach ($this->bankAccount->getCreds() as $cred)
        {
            $array['CredAllowed'][] = $this->transformBankAccountCred($cred);
        }
    }

    private function transformBankAccountCred(array $cred)
    {
        return [
            'type'        => strtoupper($cred['type']),
            'subtype'     => strtoupper($cred['sub_type']),
            'dLength'     => $cred['length'],
            'dFormat'     => $cred['format'],
        ];
    }

    private function setTxnProperties(array & $array)
    {
        $array['txnId'] = $this->txn->get('id');
        $array['note'] = $this->txn->get('note');
    }
}
