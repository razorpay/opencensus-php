<?php

namespace RZP\Models\Gateway\File;

class Status
{
    const CREATED        = 'created';
    const FILE_GENERATED = 'file_generated';
    const MAIL_SENT      = 'mail_sent';
    const FAILED         = 'failed';
    const ACKNOWLEDGED   = 'acknowledged';
}
