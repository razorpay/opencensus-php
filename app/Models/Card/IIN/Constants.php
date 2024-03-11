<?php

namespace RZP\Models\Card\IIN;

use RZP\Models\Card\IIN\Entity;

class Constants
{
    const OTP    = 'otp';
    const PIN    = 'pin';
    const ENTITY = 'entity';

    // Bin Service Entity Constants
    const FEATURES = 'features';
    const MANDATEHUBS = 'mandateHubs';
    const ISSUERNAME = 'issuerName';
    const SUBTYPE = 'subType';
    const MESSAGETYPE = 'messageType';
    const COBRANDINGPARTNER = 'cobrandingPartner';

    const BIN_SERVICE_ENTITY_TO_API_IIN_ENTITY_KEY_MAPPING = [
        self::ISSUERNAME        => Entity::ISSUER_NAME,
        self::SUBTYPE           => Entity::SUBTYPE,
        self::MESSAGETYPE       => Entity::MESSAGE_TYPE,
        self::COBRANDINGPARTNER => Entity::COBRANDING_PARTNER,
        self::MANDATEHUBS       => Entity::MANDATE_HUBS,
    ];

    const COMPARABLE_FIELDS_BETWEEN_IIN_ENTITY_AND_BIN_SERVICE = [
        Entity::IIN,
        Entity::NETWORK,
        Entity::COUNTRY,
        Entity::TYPE,
        Entity::SUBTYPE,
        Entity::ISSUER,
        Entity::MESSAGE_TYPE,
        Entity::COBRANDING_PARTNER,
        Entity::EMI,
        Entity::RECURRING,
        Entity::LOCKED,
        Entity::ENABLED,
        Entity::FLOWS,
        Entity::MANDATE_HUBS,
    ];
}
