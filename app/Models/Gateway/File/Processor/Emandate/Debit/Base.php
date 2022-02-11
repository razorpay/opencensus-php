<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Debit;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking;
use RZP\Models\Base as ModelBase;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Gateway\File\Processor\Emandate;

abstract class Base extends EMandate\Base
{
    protected $gatewayRepo;

    /**
     * For debit payments, we will be following a 9am to 9am cycle.
     * If a request comes from the cron, begin and end is set as per 12 am to 12 am cycle.
     * Adding 9 hours here to make the adjustment. If the request is generated manually then this will still
     * apply as we cannot differentiate between sync and async here. So if we try to generate the file manually
     * and put the begin and end as 9am to 9am then it will be changed to 6pm to 6pm
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
                        ->addHours(9)
                        ->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)
                      ->addHours(9)
                      ->getTimestamp();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        try
        {
            $tokens = $this->repo->token->fetchPendingEMandateDebit(static::GATEWAY, $begin, $end);
        }
        catch (ServerErrorException $e)
        {
            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ]);
        }

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_COMPLETE);

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::EMANDATE_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'count'           => count($paymentIds),
                'begin'           => $begin,
                'end'             => $end,
            ]);

        return $tokens;
    }

    /**
     * @throws GatewayFileException
     */
    public function generateData(PublicCollection $tokens): PublicCollection
    {
        try
        {
            $data = $tokens;

            // Create gateway entities
            $this->createGatewayEntities($tokens);

            return $data;
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA,
                [
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function createGatewayEntities(PublicCollection $tokens)
    {
        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $gatewayPayment = $this->gatewayRepo->findByPaymentIdAndAction(
                                    $paymentId, GatewayAction::AUTHORIZE);

            //
            // If gatewayPayment already exists then skip its creation.
            // This case will arise when we retry sending some payments to the bank
            //
            if ($gatewayPayment !== null)
            {
                continue;
            }

            $this->createGatewayEntity($token);
        }
    }

    protected function createGatewayEntity(ModelBase\PublicEntity $token)
    {
        $paymentId = $token['payment_id'];

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($paymentId);

        $gatewayPayment->setAction(GatewayAction::AUTHORIZE);

        $gatewayPayment->setBank($token['bank']);

        $gatewayPayment->setAmount($token['payment_amount']);

        $attributes = $this->getGatewayAttributes($token);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    /**
     * Override this method in the child classes in case you want
     * to add extra values in the gateway entity
     *
     * @param ModelBase\PublicEntity $token
     * @return array
     */
    protected function getGatewayAttributes(ModelBase\PublicEntity $token): array
    {
        $date = Carbon::now(Timezone::IST)->format('d/m/Y H:m:s');

        $merchant = $token->merchant;

        $attributes = [
            Netbanking\Base\Entity::MERCHANT_CODE => $merchant->getId(),
            Netbanking\Base\Entity::DATE          => $date,
        ];

        if ($merchant->isTPVRequired() === true)
        {
            $attributes[Netbanking\Base\Entity::ACCOUNT_NUMBER] = $token['account_number'];
        }

        return $attributes;
    }
}
