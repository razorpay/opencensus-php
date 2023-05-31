<?php

namespace RZP\Reconciliator\NetbankingAxis;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    // For now Axis sends only payee specific
    // refund and combined are left on here for structure
    const SUCCESS = [
        'razorpay mis report' => self::PAYMENT,
        // 'refund'        => self::REFUND,
        // 'combined'      => self::COMBINED
    ];

    /**
     * Determines the type of reconciliation
     * based on the name of the file.
     * It can either be refund, payment or combined.
     * For now, only payment.
     * we convert file name to lower case before sending
     *
     * @param string $fileName
     * @return null | string
     */
    public function getTypeName($fileName)
    {
        $typeName = null;

        foreach (self::SUCCESS as $name => $type)
        {
            if (strpos($fileName, $name) !== false)
            {
                $typeName = $type;
            }
        }

        return $typeName;
    }
}
