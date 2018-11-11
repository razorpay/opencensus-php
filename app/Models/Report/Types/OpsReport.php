<?php

namespace RZP\Models\Report\Types;

use RZP\Base\JitValidator;
use RZP\Exception;
use RZP\Models\Merchant;

class OpsReport extends BaseReport
{
    const ENACH_PENDING_REGISTRATIONS = 'enach_pending_registrations';
    const ENACH_PENDING_DEBITS        = 'enach_pending_debits';
    const GATEWAY_FAILED_REFUNDS      = 'gateway_failed_refunds';
    const IRCTC_DELTA_REFUNDS         = 'irctc_delta_refunds';

    protected static $rules = [
        'type'  => 'required|string|max:50'
    ];

    protected $types = [
        [
            'type' => self::ENACH_PENDING_REGISTRATIONS,
            'label' => 'E-NACH Pending Registrations'
        ],
        [
            'type' => self::ENACH_PENDING_DEBITS,
            'label' => 'E-NACH Pending Debits'
        ],
        [
            'type' => self::GATEWAY_FAILED_REFUNDS,
            'label' => 'Gateway failed refunds'
        ],
        [
            'type' => self::IRCTC_DELTA_REFUNDS,
            'label' => 'IRCTC delta refunds'
        ],
    ];

    public function getOpsReportTypes()
    {
        return $this->types;
    }

    public function getOpsReport($type)
    {
        $input = ['type' => $type];

        (new JitValidator)->rules(self::$rules)->input($input)->validate();

        $data = [];

        switch ($type)
        {
            case self::ENACH_PENDING_DEBITS:
                //$data =
                break;

            case self::ENACH_PENDING_REGISTRATIONS:
                //$data =
                break;

            case self::GATEWAY_FAILED_REFUNDS:
                $data = $this->repo->refund->fetchFailedRefundsByGateway();
                break;

            case self::IRCTC_DELTA_REFUNDS:
                $irctcMerchants = Merchant\Preferences::MID_IRCTC;

                $data = $this->repo->payment->fetchAuthorizedPaymentCountForMerchants($irctcMerchants);
                break;

            default:
                throw new Exception\LogicException('Not A Valid Report Type');
        }

        $data = $this->getFormattedData($data);

        return $data;
    }

    protected function getFormattedData($entities)
    {
        $data = [];

        foreach ($entities as $entity)
        {
            $row = [];

            $attributes = $entity->getAttributes();

            foreach ($attributes as $key => $value)
            {
                $row[$key] = $value;
            }

            $data[] = $row;
        }

        return $data;
    }
}
