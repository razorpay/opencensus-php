<?php

namespace RZP\Models\Partner\Commission;

interface CommissionSourceInterface
{
    public function getEntity();

    public function merchant();

    public function getCurrency();

    public function transaction();
}
