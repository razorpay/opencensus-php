<?php

namespace RZP\Models\Options;

class Constants
{

    // namespaces
    const NAMESPACE_PAYMENT_LINKS                       = 'payment_links';
    const NAMESPACE_INVOICES                            = 'invoices';
    const NAMESPACE_PAYMENT_PAGES                       = 'payment_pages';

    const ALLOWED_NAMEPSPACES                           = array(self::NAMESPACE_PAYMENT_LINKS);

    // services
    const SERVICE_PAYMENT_LINKS                         = 'invoices';
    const SERVICE_INVOICES                              = 'invoices';
    const SERVICE_PAYMENT_PAGES                         = 'invoices';

    const ALLOWED_SERVICES                              = array(self::SERVICE_PAYMENT_LINKS);
    // reference id
    const REFERENCE_ID                                  = 'reference_id';
    // scopes : merchant options are always of global scope and options created via API are of entity scope
    const SCOPE_GLOBAL       			                = 'global';
    const SCOPE_ENTITY           		              	= 'entity';
    // json keys
    const DEFAULT_OPTIONS                               = 'defaultOptions';
    const MERCHANT_OPTIONS                              = 'merchantOptions';
    const SERVICE_OPTIONS                               = 'serviceOptions';
    const MERGED_OPTIONS                                = 'mergedOptions';
    // error messages
    const ERROR_MSG_DUPLICATE_NS_FIELD                  = 'Entry with field %s=%s already exists for merchant. '
    .'You may want to update or delete existing';
    const ERROR_MSG_DUPLICATE_SERVICE_REF_ID_FIELD      = 'Entry with fields %s=%s and %s=%s and %s=%s '
    .'already exists for merchant. You may want to update or delete existing';
    const ERROR_MSG_NO_ENTITY_FOR_MID                   = 'No entity with namespace=%s, service=%s and '
    .'merchant=%s found';
    const ERROR_MSG_NAMESPACE_NOT_SUPPORTED             = 'Namespace %s is not valid';
    const ERROR_MSG_SERVICE_NOT_SUPPORTED               = 'Service %s is not valid';

}
