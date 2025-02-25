
/**
 * Represents an invitation for the user.
 */
export type RazorpayUserUserInvitation = {
    /** Invitation unique identifier */
    id: string;
  
    /** Email address associated with the invitation */
    email: string;
  
    /** Contact mobile number associated with the invitation, if any */
    contact_mobile: string | null;
  
    /** Metadata for the invitation */
    metadata: any[];
  
    /** Role of the user in the invitation */
    role: string;
  
    /** User ID associated with the invitation */
    user_id: string;
  
    /** Merchant ID associated with the invitation */
    merchant_id: string;
  
    /** Product export type associated with the invitation */
    product: string;
  
    /** Boolean flag indicating if the invitation is a draft */
    is_draft: boolean;
  
    /** Merchant name associated with the invitation */
    merchant_name: string;
  };