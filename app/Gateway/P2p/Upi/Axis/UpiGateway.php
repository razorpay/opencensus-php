<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use RZP\Models\P2p\Transaction;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;
use RZP\Gateway\P2p\Upi\Axis\Transformers\TransactionTransformer;
use RZP\Gateway\P2p\Upi\Axis\Transformers\UpiTransactionTransformer;

class UpiGateway extends Gateway implements Contracts\UpiGateway
{
    public function gatewayCallback(Response $response)
    {
        switch ($this->input->get(Fields::CONTENT)[Fields::TYPE])
        {
            case TransactionAction::COLLECT_REQUEST_RECEIVED:

                $transformer = new UpiTransactionTransformer($this->input->get(Fields::CONTENT));
                $upi = $transformer->transformIncoming();

                $transformer = new TransactionTransformer($this->input->get(Fields::CONTENT));
                $transaction = $transformer->transformIncoming();

                break;
        }

        $response->setData([
            Transaction\Entity::TRANSACTION     => $transaction,
            Transaction\Entity::UPI             => $upi,
        ]);
    }
}
