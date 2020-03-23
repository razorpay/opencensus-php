<?php

namespace RZP\Models\Typeform;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\lib\TypeformParser;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Entity as Merchant;
use \RZP\Models\Merchant\Core as MerchantCore;

class Core extends Base\Core
{

    /**
     * @param array  $input
     * @param string $currentRoute
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function processTypeformWebhook(array $input)
    {
        $merchant = $this->fetchMerchant($input);

        $this->app['basicauth']->setMerchant($merchant);

        $parser = new TypeformParser($input);

        $this->trace->info(TraceCode::TYPEFORM_RAW_DATA, ['mid' => $merchant->getId()]);

        $typeformWorkflowData = $parser->parseWebhookData();

        $this->trace->info(TraceCode::TYPEFORM_PARSED_DATA,
                           ['mid'        => $merchant->getId(),
                            'parsedData' => $typeformWorkflowData]);

        $this->createInternationalWorkflow($merchant, $typeformWorkflowData);

        return ['success' => true];
    }

    /**
     * @param array $input
     *
     * @return mixed
     * @throws Exception\BadRequestException
     */
    private function fetchMerchant(array $input)
    {
        if ((array_key_exists('hidden', $input['form_response'])) and
            (array_key_exists('mid', $input['form_response']['hidden'])))
        {
            $merchantId = $input['form_response']['hidden']['mid'];

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            return $merchant;
        }
        else
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PRESENT,
                null,
                ['data' => $input['event_id']]
            );
        }
    }

    /**
     * @param Merchant $merchant
     * @param array    $typeformWorkflowData
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    private function createInternationalWorkflow(Merchant $merchant, array $typeformWorkflowData)
    {
        $this->app['workflow']
            ->setEntityAndId($merchant->getEntity(), $merchant->getId())
            ->setPermission(Permission\Name::EDIT_MERCHANT_INTERNATIONAL)
            ->handle(null, $typeformWorkflowData);

        $this->trace->info(TraceCode::TYPEFORM_WORKFLOW_TRIGGERED,
                           ['entity'  => $merchant->getEntity(),
                            'enityId' => $merchant->getId()]);

        $merchantCore = new MerchantCore();

        $merchantCore->updateInternationalIfApplicable($merchant, $merchant->merchantDetail);

        $this->repo->saveOrFail($merchant);
    }

}
