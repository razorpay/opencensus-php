
/**
 * Represents debit EMI providers available for the merchant.
 */
export type RazorpayUserMerchantDebitEmiProviders = {
    /** Boolean flag for HDFC debit EMI provider */
    HDFC: number;
  
    /** Boolean flag for Kotak Mahindra Bank (KKBK) debit EMI provider */
    KKBK: number;
  
    /** Boolean flag for Indian Bank (INDB) debit EMI provider */
    INDB: number;
  
    /** Boolean flag for ICICI debit EMI provider */
    ICIC: number;
  
    /** Other providers, if available */
    [key: string]: number;
  };
  