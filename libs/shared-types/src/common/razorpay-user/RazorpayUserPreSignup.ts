

/**
 * Represents pre-signup information for the user.
 */
export type RazorpayUserPreSignup = {
    /** Business export type identifier */
    business_type: string;
  
    /** Transaction volume, if available */
    transaction_volume: string | null;
  
    /** Contact name */
    contact_name: string;
  
    /** Business name */
    business_name: string;
  
    /** Contact mobile number */
    contact_mobile: string;
  };