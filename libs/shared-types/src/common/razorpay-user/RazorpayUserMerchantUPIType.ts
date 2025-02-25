/**
 * Represents UPI transaction types.
 */
export type RazorpayUserMerchantUPIType = {
    /** Boolean flag for Collect export type of UPI transaction */
    collect: number;
  
    /** Boolean flag for Intent export type of UPI transaction */
    intent: number;
  
    /** Other providers, if available */
    [key: string]: number;
  };