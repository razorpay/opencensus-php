<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer;

interface ClarificationReasonComposerInterface
{
    public function getClarificationReason() : array;
}