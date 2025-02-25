
/**
 * Represents available card networks for the merchant.
 */
export type RazorpayUserMerchantCardNetworks = {
    /** Boolean flag for AMEX network availability (1 means enabled, 0 means disabled) */
    AMEX: number;
  
    /** Boolean flag for DICL network availability */
    DICL: number;
  
    /** Boolean flag for MasterCard network availability */
    MC: number;
  
    /** Boolean flag for Maestro network availability */
    MAES: number;
  
    /** Boolean flag for VISA network availability */
    VISA: number;
  
    /** Boolean flag for JCB network availability */
    JCB: number;
  
    /** Boolean flag for RuPay network availability */
    RUPAY: number;
  
    /** Boolean flag for Bajaj network availability */
    BAJAJ: number;
  
    /** Boolean flag for UNP (Unknown Payment Network) availability */
    UNP: number;
  
    /** Other availability, if available */
    [key: string]: number;
  };