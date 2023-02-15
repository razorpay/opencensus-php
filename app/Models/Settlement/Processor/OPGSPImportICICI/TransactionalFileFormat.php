<?php

namespace RZP\Models\Settlement\Processor\OPGSPImportICICI;

class TransactionalFileFormat
{
    public $Date = "NA";
    public $OPGSPTransactionRefNo = "NA";
    public $INRAmount = "NA";
    public $CURRENCY = "NA";
    public $IBANNumber = "";
    public $CNAPSCode = "";
    public $POPSCode = "";
    public $BeneficiaryAccountNumber = "NA";
    public $BeneficiaryName = "NA";
    public $BeneficiaryAddress1 = "NA";
    public $BeneficiaryAddress2 = "";
    public $BeneficiaryCountry = "NA";
    public $BeneficiaryBankBICCode = "NA";
    public $BeneficiaryBankName = "NA";
    public $BeneficiaryBankAdd = "NA";
    public $BeneficiaryBankCountry = "NA";
    public $IntermediaryBankBICCode = "";
    public $IntermediaryBankName = "";
    public $IntermediaryBankAddress = "";
    public $IntermediaryBankCountry = "";
    public $RemittanceInfo = "";
    public $InvoiceNumber = "NA";
    public $InvoiceDate = "NA";
    public $CommodityCode = "NA";
    public $CommodityDescription = "";
    public $Quantity = "";
    public $Rate = "";
    public $HSCode = "";
    public $HSCodeDescription = "";
    public $BuyerName = "NA";
    public $BuyerAddress = "NA";
    public $PurposeOfRemittance = "NA";
    public $PaymentTerms = "Advance";
    public $IECode = "Import for Personal use";
    public $AirwayBill = "NA";
    public $TransactionAmount = "NA";
    public $RequestedAction = "NA";
    public $RequestID = "NA";
    public $ProductInfo = "";
    public $MID = "NA";
    public $ProcessingFee = "NA";
    public $GST = "NA";
    public $MerchantTransactionId = "";
    public $PAN = "";
    public $DOB = "";
    public $Mode = "";
    public $PgLabel = "";
    public $CardType = "";
    public $IssuingBank = "";
    public $BankRefNumber = "";

    public function getAssocArray(){
        return (array)$this;
    }
}
