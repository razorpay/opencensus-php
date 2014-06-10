<?php

namespace Constants;

final class URL
{
    const TXN_CREATE = 'transactions';

    const TXN_REFUND = 'transactions/{id}/refund';

    const TXN_CAPTURE = 'transactions/{id}/capture';

    const TXN_JSONP = 'transactions/jsonp';

    const TXN_RETRIEVE = 'transactions/{param?}';

    const TXN_CALLBACK = 'transactions/callback';
}