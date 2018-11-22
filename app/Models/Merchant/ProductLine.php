<?php

namespace RZP\Models\Merchant;

/**
 * Suit of offerings to a merchant e.g. Payment gateway, Business banking.
 * This enum values are to be used in various places - e.g. balance.product_line etc.
 */
class ProductLine
{
    const PG               = 'pg';
    const BUSINESS_BANKING = 'business_banking';
}
