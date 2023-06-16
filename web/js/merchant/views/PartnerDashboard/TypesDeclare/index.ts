export interface UserT extends Record<string, unknown> {
  // TS_TODO : Add missing fields are per need
  isPartnershipForXEnabled: boolean;
  isSubMerchantKycResellerEnabled: boolean;
  isPartnershipNPS: boolean;
  isPartnershipFUX: boolean;
  isOnboardAsResellers: boolean;
  isShowInvoiceCurrentFY: boolean;
  isShowAffordabilityWidget: boolean;
  isShowAffWidgetShopifyWaitlist: boolean;
  isShowAffWidgetWoocWaitlist: boolean;
  isShowSegregatedCreditEmi: boolean;
  isShowResumeOnboarding: boolean;
  isEnablePurePlatformSwitch: boolean;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type TODO_PD = any;
