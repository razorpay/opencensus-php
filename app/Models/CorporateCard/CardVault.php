<?php

namespace RZP\Models\CorporateCard;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class CardVault extends Base\Core
{
    /**
     * @var \RZP\Services\CardVault
     */
    private $cardVault;

    public function __construct()
    {
        parent::__construct();
        $this->cardVault = $this->app['razorpayx.cardVault'];
    }

    public function getVaultToken($input) // use this
    {
        try
        {
            $cardNumber = preg_replace('/[^0-9]/', '', $input['card']);
            $input['card'] = $cardNumber;
            $token = $this->cardVault->tokenize($input);

            return $token;
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'message' => 'Failed to tokenize data'
                ]
            );

            throw $e;
        }
    }
}
