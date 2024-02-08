export enum DisabledInternationalCardsReasons {
  NOT_ACTIVATED = 'NOT_ACTIVATED',
  RISK_FOH = 'RISK_FOH',
  UNREGISTERED = 'UNREGISTERED',
  NO_WEBSITE_DETAILS = 'NO_WEBSITE_DETAILS',
}

export enum ProductWorkflowStatesInBackend {
  REJECTED = 'rejected',
  IN_REVIEW = 'in_review',
  APPROVED = 'approved',
  NO_ACTION_RECEIVED = 'no_action_received',
}

export type MerchantICProductStatus = {
  payment_gateway: ProductWorkflowStatesInBackend;
  payment_links: ProductWorkflowStatesInBackend;
  payment_pages: ProductWorkflowStatesInBackend;
  invoices: ProductWorkflowStatesInBackend;
  products_pa_cb: ProductWorkflowStatesInBackend;
};

export type CommonICProductsState = {
  isAnyProductApproved: boolean;
  isAnyProductRequested: boolean;
  hasUserDisabledInternationalCards: boolean;
  isAnyProductInReview: boolean;
  isAnyProductRejected: boolean;
  isNoProductApprovedOrInReview: boolean;
};

export enum InternationalCardsRejectionCodes {
  CLARIFICATION_NOT_PROVIDED = 'clarification_not_provided',
  WEBSITE_DETAIL_INCOMPLETE = 'website_detail_incomplete',
  RISK_REJECTION = 'risk_rejection',
  MERCHANT_HIGH_CHARGEBACKS_FRAUD_PRESENT = 'merchant_high_chargebacks_fraud_present',
  BUSINESS_MODEL_MISMATCH = 'business_model_mismatch',
  DORMANT_MERCHANT = 'dormant_merchant',
  RESTRICTED_BUSINESS = 'restricted_business',
  INVALID_DOCUMENTS = 'invalid_documents',
}

export enum ICProductStates {
  ACTIVE = 'ACTIVE',
  REJECTED = 'REJECTED',
  UNDER_REVIEW = 'UNDER REVIEW',
  ACTION_REQUIRED = 'ACTION REQUIRED',
  NOT_ACTIVATED = 'NOT_ACTIVATED',
}

export enum ProductTypeForAnalytics {
  All = 'All',
  PG = 'PG',
  PPLI = 'PP PL Inv',
}

export enum BannerType {
  APPROVED = 'approved',
  UNDER_REVIEW = 'under_review',
  UNDER_REVIEW_BREACHED = 'under_review_breached',
  UNDER_REVIEW_BREACHED_AGAIN = 'under_review_breached_again',
  NEEDS_CLARIFICATION = 'needs_clarification',
  REJECTED = 'rejected',
  WEBSITE_DETAIL_UPDATE = 'website_detail_update',
}

export const bannerTypeProductStatusMapping: Partial<Record<BannerType, ICProductStates>> = {
  [BannerType.APPROVED]: ICProductStates.ACTIVE,
  [BannerType.NEEDS_CLARIFICATION]: ICProductStates.ACTION_REQUIRED,
  [BannerType.UNDER_REVIEW]: ICProductStates.UNDER_REVIEW,
  [BannerType.UNDER_REVIEW_BREACHED]: ICProductStates.UNDER_REVIEW,
  [BannerType.UNDER_REVIEW_BREACHED_AGAIN]: ICProductStates.UNDER_REVIEW,
  [BannerType.REJECTED]: ICProductStates.REJECTED,
};
