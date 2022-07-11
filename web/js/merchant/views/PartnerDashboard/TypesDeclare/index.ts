export interface UserT extends Record<string, unknown> {
  // TS_TODO : Add missing fields are per need
  isPartnershipForXEnabled: boolean;
  isSubMerchantKycResellerEnabled: boolean;
  isMerchantValidation: boolean;
  isPartnershipNPS: boolean;
  isPartnershipFUX: boolean;
  isOnboardAsResellers: boolean;
}
