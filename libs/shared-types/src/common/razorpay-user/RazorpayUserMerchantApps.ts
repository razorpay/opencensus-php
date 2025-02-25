

/**
 * Represents third-party apps for payment support.
 */
export type RazorpayUserMerchantApps = {
    /** Boolean flag for CRED app support */
    cred: number;
  
    /** Boolean flag for Twid app support */
    twid: number;
  
    /** Boolean flag for Trustly app support */
    trustly: number;
  
    /** Boolean flag for Poli app support */
    poli: number;
  
    /** Boolean flag for Sofort app support */
    sofort: number;
  
    /** Boolean flag for GiroPay app support */
    giropay: number;
  
    /** Other app support, if available */
    [key: string]: number;
  };
  