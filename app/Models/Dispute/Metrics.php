<?php

namespace RZP\Models\Dispute;

class Metrics
{
    // Counters type metric names
    const DISPUTE_MAIL_SUCCESS                          = 'dispute_mail_success';
    const DISPUTE_FD_MAIL_FAILURE                       = 'dispute_fd_mail_failure';
    const DISPUTE_FD_MAIL_MERCHANTS_WITH_NO_EMAIL       = 'dispute_fd_mail_merchants_with_no_email';
    const DISPUTE_CREATE                                = 'dispute_create';
    const DISPUTE_STATUS_CHANGE                         = 'dispute_status_change';
    const DISPUTE_DUAL_WRITE_SHADOW_MODE_FAILURE        = 'dispute_dual_write_shadow_mode_failure';
}
