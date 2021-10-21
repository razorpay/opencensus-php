<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Constants\Mode;

class Repository extends Base\Repository
{
    protected $entity = 'qr_code';

    public function findByMerchantReference(string $merchantReference)
    {
        return $this->newQuery()
                    ->where(Entity::REFERENCE, '=', $merchantReference)
                    ->first();
    }

    public function determineLiveOrTestModeByMerchantReference($merchantReference)
    {
        $obj = $this->connection(Mode::LIVE)->findByMerchantReference($merchantReference);

        if ($obj !== null)
        {
            return Mode::LIVE;
        }

        $obj = $this->connection(Mode::TEST)->findByMerchantReference($merchantReference);

        if ($obj !== null)
        {
            return Mode::TEST;
        }

        //
        // We need to set connection to null
        // because it will be set to test if the
        // id is not found in any of the database.
        // So even if the db connection is later set
        // to live, query connection will be set to
        // test.
        //
        $this->connection(null);

        return null;
    }

    public function fetchQrCodesForMpanTokenization($count)
    {
        $mpanTokenized = $this->dbColumn(Entity::MPANS_TOKENIZED);

        $qrString = $this->dbColumn(Entity::QR_STRING);

        $provider = $this->dbColumn(Entity::PROVIDER);

        $createdAt = $this->dbColumn(Entity::CREATED_AT);
        
        return $this->newQuery()
                     ->take($count)
                     ->where($provider, '=', 'bharat_qr')
                     ->whereNotNull($qrString)
                     ->whereNull($mpanTokenized)
                     ->where($createdAt, '>', 1552500000) // picking only after 13 mar 2019, as before this date 16 digit mc mpan was stored. https://github.com/razorpay/api/commit/34e9256fc94dc9c61e75f60b993f30a48ef48186 
                     ->get();
    }
}
