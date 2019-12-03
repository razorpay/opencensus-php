<?php


namespace RZP\Models\Options\Helpers\Factory;

use RZP\Models\Options\Constants;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Options\Helpers\DefaultOption;
use RZP\Models\Options\Helpers\PaymentLinkDefaultOption;

class DefaultOptionFactory
{
    /**
     *  Supplies the default options defined for needed namespace.
     *
     *  NOTE : Defined only payment_links now. Add as needed in future
     *
     * @param  String  $namespace
     * @return DefaultOption
     * @throws InvalidArgumentException
     */
    public static function find(String $namespace): DefaultOption
    {
        switch ($namespace) {
            case Constants::NAMESPACE_PAYMENT_LINKS :
            {
                return new PaymentLinkDefaultOption();
            }
            default :
            {
                throw new InvalidArgumentException('No default option defined for namespace : '.$namespace);
            }
        }
    }
}