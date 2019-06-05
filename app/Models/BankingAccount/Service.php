<?php

namespace RZP\Models\BankingAccount;

use RZP\Exception\LogicException;
use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input): array
    {
        (new Validator)->setStrictFalse()->validateInput('pre_create', $input);

        $channel = $input[Entity::CHANNEL];

        switch ($channel)
        {
            case Channel::RBL:
                $account = $this->core->createRblBankingAccount($input, $this->merchant);

                break;

            default:
                throw new LogicException(
                    'Banking Account logic undefined for channel: ' . $channel,
                    null,
                    $input);
        }

        return $account->toArrayPublic();
    }

    public function addOrRemoveServiceablePincodes(array $input, $channel)
    {
        $input[Entity::CHANNEL] = $channel;

        (new Validator)->validateInput('serviceablePincode', $input);

        $coreMethod = $input[Entity::ACTION] . 'ServiceablePincodes';

        switch ($channel)
        {
            case Channel::RBL:
                $coreMethod = $coreMethod . 'ForRbl';

                $result = $this->core->$coreMethod($input[Entity::PINCODES]);

                break;

            default:
                throw new LogicException(
                    'Banking Account logic undefined for channel: ' . $channel,
                    null,
                    $input);
        }

        return $result;
    }
}
