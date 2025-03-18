<?php

namespace RZP\Models\BankTransfer\Collectx;

class Constants {
    const TRANSFER_TYPE_UPI = "UPI";
    const TRANSFER_TYPE_NEFT = "NEFT";
    const TRANSFER_TYPE_RTGS = "RTGS";
    const TRANSFER_TYPE_IMPS = "IMPS";
    const TRANSFER_TYPE_FT = "FT";
    const TRANSFER_TYPE_IFT = "IFT";
    const TRANSFER_TYPE_TRANSFER = "TRANSFER";
    const COLLECTX_DEFAULT_FEE_CREDITS_THRESHOLD = 50000;
    const VALIDATION_CALLBACK = "validation";
    const NOTIFICATION_CALLBACK = "notification";

    const BANK_TRANSFER = "bank_transfer";

    const UPI_TRANSFER = "upi_transfer";
}
