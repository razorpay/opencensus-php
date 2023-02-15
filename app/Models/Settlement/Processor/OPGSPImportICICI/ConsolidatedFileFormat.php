<?php

namespace RZP\Models\Settlement\Processor\OPGSPImportICICI;

class ConsolidatedFileFormat
{

    public $Date = "NA";
    public $OPGSPTranRefNo = "NA";
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
    public $CommodityCode = "NA";
    public $CommodityDescription = "";
    public $HSCode = "";
    public $HSCodeDescription = "";
    public $PurposeOfRemittance = "NA";
    public $PaymentTerms = "Advance";
    public $IECode = "NA";
    public $TIDMin = "";
    public $TIDMax = "";

    public function getAssocArray(){
        return (array)$this;
    }
}
