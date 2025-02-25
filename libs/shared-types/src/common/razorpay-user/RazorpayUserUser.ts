import { RazorpayUserUserInvitation } from "./RazorpayUserUserInvitation";
import { RazorpayUserUserSettings } from "./RazorpayUserUserSettings";

/**
 * Represents user details within RazorpayUser.
 */
export type RazorpayUserUser = {
    /** User unique identifier */
    id: string;
  
    /** Name of the user */
    name: string;
  
    /** User's email address */
    email: string;
  
    /** Contact mobile number */
    contact_mobile: string;
  
    /** Boolean flag indicating if the mobile contact is verified */
    contact_mobile_verified: boolean;
  
    /** Boolean flag indicating if the email is verified */
    email_verified: boolean;
  
    /** Boolean flag for second factor authentication */
    second_factor_auth: boolean;
  
    /** Boolean flag for second factor authentication enforcement */
    second_factor_auth_enforced: boolean;
  
    /** Boolean flag indicating if second factor authentication is set up */
    second_factor_auth_setup: boolean;
  
    /** Boolean flag indicating if second factor authentication is enforced by the organization */
    org_enforced_second_factor_auth: boolean;
  
    /** Boolean flag for restricted account access */
    restricted: boolean;
  
    /** Boolean flag indicating if the account is confirmed */
    confirmed: boolean;
  
    /** Boolean flag indicating if the account is locked */
    account_locked: boolean;
  
    /** Timestamp when the user account was created */
    created_at: number;
  
    /** Sign-up method identifier (1 if via email) */
    signup_via_email: number;
  
    /** Metadata for the user */
    metadata: any | null;
  
    /** List of invitations associated with the user */
    invitations: RazorpayUserUserInvitation[];
  
    /** User settings object */
    settings: RazorpayUserUserSettings;

    signup_campaign: string;
  };