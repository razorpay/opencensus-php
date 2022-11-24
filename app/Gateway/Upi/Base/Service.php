<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Exception\LogicException;
use RZP\Models\Base\Service as BaseService;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Trace\TraceCode;

class Service extends BaseService
{
    public function dataCorrection(string $action, array $input)
    {
        try
        {
            switch ($action)
            {
                case 'sbi_rrn_correction':

                    $data = $this->sbiRrnCorrection($input);

                    break;

                default:
                    throw new BadRequestValidationFailureException('Invalid action', $action);
            }
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception, null, null, [
                'message'   => 'UPI data correction',
                'success'   => false,
                'action'    => $action,
                'input'     => $input,
            ]);

            throw $exception;
        }

        $this->trace->info(TraceCode::MISC_TRACE_CODE, [
            'message'   => 'UPI data correction',
            'success'   => true,
            'action'    => $action,
            'input'     => $input,
            'output'    => $data,
        ]);

        return $data;
    }

    protected function sbiRrnCorrection(array $input)
    {
        (new Validator())->validateInput(__FUNCTION__, $input);

        $input['filter'][] = ['gateway', '=', 'upi_sbi'];

        $input['select'] = [
            Entity::ID,
            Entity::PAYMENT_ID,
            Entity::GATEWAY,
            Entity::NPCI_REFERENCE_ID,
            Entity::GATEWAY_PAYMENT_ID
        ];

        $entities = $this->repo->upi->findByMatchingNpciReferenceId(
            $input['match'],
            $input['select'],
            $input['count'],
            $input['filter']);

        $ids = [];

        foreach ($entities as $entity)
        {
            // We need fetch originals as the data is swapped in the getter
            $npciReferenceId    = $entity->getRawOriginal(Entity::NPCI_REFERENCE_ID);
            $gatewayPaymentId   = $entity->getRawOriginal(Entity::GATEWAY_PAYMENT_ID);

            // If gateway payment id is not rrn, we need stop processing there itself.
            // And in order to fix the data we will need to pass the filter skipping the id
            if (strlen($gatewayPaymentId) !== 12)
            {
                throw new LogicException('Gateway Payment Id is not RRN', null, $entity->toArray());
            }

            $entity->setNpciReferenceId($gatewayPaymentId);
            $entity->setGatewayPaymentId($npciReferenceId);

            $this->repo->saveOrFail($entity);

            $ids[] = $entity->getPaymentId();
        }

        return [
            'success'   => true,
            'ids'       => $ids
        ];
    }
}
