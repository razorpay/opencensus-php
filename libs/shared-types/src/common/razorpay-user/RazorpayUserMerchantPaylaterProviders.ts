

/**
 * Represents pay later providers available for the merchant.
 */
export type RazorpayUserMerchantPaylaterProviders = {
    /** Boolean flag for Simpl pay later provider */
    getsimpl: number;
  
    /** Boolean flag for LazyPay pay later provider */
    lazypay: number;
  
    /** Boolean flag for ICICI pay later provider */
    icic: number;
  
    /** Boolean flag for HDFC pay later provider */
    hdfc: number;
  
    /** Boolean flag for Amazon Pay Later provider */
    amazonpay: number;
  
    /** Boolean flag for Razorpay Postpaid provider */
    rzpx_postpaid: number;
  
    /** Other providers, if available */
    [key: string]: number;
  };
  