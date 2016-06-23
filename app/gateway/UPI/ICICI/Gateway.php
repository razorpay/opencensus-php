<?php

namespace Gateway\UPI\ICICI;

use Gateway\Base;

class Gateway extends Base\Gateway
{
    const ICICI_PUBLIC_KEY = <<<EOT
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAmj05pbyW0V0S2LDT5zNc
lAoZevw+2vjyGBQVTBLHJ1PL9zH+TBGe6+uR6QMoF7KG1/yqILaOAmV4K2T00O4I
hp6EoX4EdLt1E/VNpPMOhUbhxwHJ7KD8t4BEGjDRpbdBG+XOsLaXmKRty771ek0V
i8Umbo3IUYoQuC6DIqTCXZmhxnBNd1FAikPoM9mdwFY0/PqQ92XUPmUNTZ7sEzhk
oBrtFTcqnPacPJPa1y6n2YFmUmzv9wnFZ55OGwcvpNiI/GOjmmgemkQp6Vkleo7H
JqoGvsqK1QG54rFhuuTSxGARFhH3wKEB4lGsJ9D1mTGUOnafC4iOC0SAk5mTrKbm
uJdavD1TXAkhXlNs5oVJhQm1UPKtZwqpYlDWz3ybBs26412Nl/wXCshcksA/jPZS
K0sTxEWHjJ7MLyNAoDDV+Gko6BaxURAjX86Ac930tBt2/LIdNUlT+z+uTldsHO1I
dbNHrDYms1ZEIzVV83oN/Hev3Oae+tSWrGQRWvV9rqHByDFlsniwnYhLO6XyHvYq
dPGKC553wEbHtJqPTaupDCY/49d7pVAWGFpVob6ebg8R51yk4mgoEaeg6s9KpMce
RQAfGbcw2gk+LU1nxcgexz0piV0aCTWw1rD+v+O5n1AGOf+5qWUu6H8wqfJtyxGD
N3gj6mi9EFGymEcgFWhhaO0CAwEAAQ==
-----END PUBLIC KEY-----
EOT;
    protected $gateway = 'upi_icici';

    protected function genKey()
    {
        return random_alphanum_string(32);
    }

    /** Generates request object for the status call */
    protected function statusData()
    {
        return [
            "merchantId"        =>  "merchantId",
            "subMerchantId"     =>  "12234",
            "terminalId"        =>  "2342342",
            "merchantTranId"    =>  "612413726581"
        ];
    }


    protected function collectPayData()
    {
        sd($this->config);
        return [
            // Amount and note are lowercase
            // despite being uppercase in docs
            "amount"        =>  "5.00",
            "billNumber"    =>  "sdf234234",
            "collectByDate" =>  "15/06/2016 11:01 AM",
            "merchantId"    =>  "merchantId",
            "merchantName"  =>  "merchantName",
            "merchantTranId"=>  "345345345",
            "note"          =>  "collect-pay-request",
            "payerVa"       =>  "testing1@imobile",
            "subMerchantId" =>  "12234",
            "subMerchantName"=> null,
            "terminalId"    =>  "2342342",
        ];
    }
}
