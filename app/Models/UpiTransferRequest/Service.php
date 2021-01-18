<?php


namespace RZP\Models\UpiTransferRequest;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function create(array $input, $requestPayload)
    {
        try
        {
            $this->convertPayeeVpaToLower($input);

            return $this->core->create($input, $requestPayload);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::UPI_TRANSFER_SAVE_REQUEST_FAILED,
                [
                    Entity::GATEWAY           => $input[Entity::GATEWAY],
                    Entity::NPCI_REFERENCE_ID => $input[Entity::NPCI_REFERENCE_ID],
                ]
            );
        }
        return null;
    }

    protected function convertPayeeVpaToLower(array & $input)
    {
        $payeeVpa = $input['payee_vpa'];

        $input['payee_vpa'] = strtolower($payeeVpa);
    }
}
