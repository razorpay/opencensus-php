<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive;

use RZP\Models\P2p\Complaint\Action;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\P2p\Complaint\Entity;
use RZP\Gateway\P2p\Upi\AxisOlive\ErrorMap;
use RZP\Gateway\P2p\Upi\AxisOlive\Actions\TurboAction;
use RZP\Gateway\P2p\Upi\AxisOlive\Transformers\Transformer;
use RZP\Gateway\P2p\Upi\AxisOlive\Transformers\ComplaintTransformer;

/**
 * Class TurboGateway
 * This is a class for turbo gateway
 * @package RZP\Gateway\P2p\Upi\AxisOlive
 */
class TurboGateway extends Gateway implements Contracts\TurboGateway
{

    public function initiateTurboCallback(Response $response)
    {
        $content = $this->input->get(Fields::CONTENT);

        $transformer  = new Transformer($content);

        $type = $transformer->determineCallbackType();

        switch ($type)
        {
            case TurboAction::REQUEST_COMPLAINT_CALLBACK:
            case TurboAction::NOTIFICATION_COMPLAINT_CALLBACK:
                $complaintTransformer   = new ComplaintTransformer($content, $type);
                $complaint              = $complaintTransformer->transform();

                $context = [
                    Entity::ENTITY  => Entity::COMPLAINT,
                    Entity::ACTION  => Action::INCOMING_CALLBACK,
                    Fields::TYPE    => TurboAction::REQUEST_COMPLAINT_CALLBACK,
                ];

                $response->setData([
                    Fields::COMPLAINT  => $complaint,
                    Fields::CONTEXT    => $context,
                    Entity::RESPONSE   => [
                        Entity::SUCCESS => true,
                    ]
                ]);

                return;

            default:
                throw $this->p2pGatewayException(ErrorMap::INVALID_CALLBACK, [
                    'input' => $this->input->toArray(),
                ]);
        }
    }
}
