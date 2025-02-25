
/**
 * Represents credit EMI providers available for the merchant.
 */
export type RazorpayUserMerchantCreditEmiProviders = {
    /** Boolean flag for HDFC credit EMI provider */
    HDFC: number;
  
    /** Boolean flag for State Bank of India (SBIN) credit EMI provider */
    SBIN: number;
  
    /** Boolean flag for Axis Bank (UTIB) credit EMI provider */
    UTIB: number;
  
    /** Boolean flag for ICICI credit EMI provider */
    ICIC: number;
  
    /** Boolean flag for AMEX credit EMI provider */
    AMEX: number;
  
    /** Boolean flag for Bank of Baroda (BARB) credit EMI provider */
    BARB: number;
  
    /** Boolean flag for Citibank (CITI) credit EMI provider */
    CITI: number;
  
    /** Boolean flag for HSBC credit EMI provider */
    HSBC: number;
  
    /** Boolean flag for Indian Bank (INDB) credit EMI provider */
    INDB: number;
  
    /** Boolean flag for Kotak Mahindra Bank (KKBK) credit EMI provider */
    KKBK: number;
  
    /** Boolean flag for RBL Bank (RATN) credit EMI provider */
    RATN: number;
  
    /** Boolean flag for Standard Chartered Bank (SCBL) credit EMI provider */
    SCBL: number;
  
    /** Boolean flag for Yes Bank (YESB) credit EMI provider */
    YESB: number;
  
    /** Boolean flag for OneCard credit EMI provider */
    onecard: number;
  
    /** Boolean flag for Bajaj credit EMI provider */
    BAJAJ: number;
  
    /** Boolean flag for Federal Bank (FDRL) credit EMI provider */
    FDRL: number;
  
    /** Boolean flag for IDFC First Bank (IDFB) credit EMI provider */
    IDFB: number;
  
    /** Other providers, if available */
    [key: string]: number;
  };
  