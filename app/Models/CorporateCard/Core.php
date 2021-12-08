<?php
namespace RZP\Models\CorporateCard;

use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class Core extends \RZP\Models\Base\Core
{
    protected $card = null;

    public function create($input, $merchant): Entity
    {
        $card = (new Entity)->build($input);

        $this->setVaultToken($card, $input);

        $card->merchant()->associate($merchant);

        $user = $this->app['basicauth']->getUser();
        $userId = $user ? $user->getUserId() : null;
        $card->setCreatedBy($userId);
        $card->setUpdatedBy($userId);

        $this->card = $card;

        $this->repo->saveOrFail($card);

        return $card;
    }

    public function edit(Entity $card, array $input)
    {
        $card->edit($input);

        $user = $this->app['basicauth']->getUser();

        $card->setUpdatedBy($user ? $user->getUserId() : null);

        $this->card = $card;

        $card->saveOrFail();

        return $card;
    }

    public function setVaultToken(Entity $card, array $input)
    {
        try
        {
            $cardVault = (new CardVault);

            $tempInput['card'] = $input['number'];

            $token = $cardVault->getVaultToken($tempInput);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::VAULT_ENCRYPTION_FAILED
            );

            return;
        }

        $card->setVaultToken($token);
    }
}
