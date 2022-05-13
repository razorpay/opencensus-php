export interface FUXStatusT {
  api_integration: boolean;
  first_commission_payout: boolean;
  first_earning_generated: boolean;
  first_submerchant_added: boolean;
  first_submerchant_accept_payments: boolean;
}

export interface FUXStatusStateT {
  value: FUXStatusT | null;
  isFetching: boolean;
}

export interface StepContentT {
  title: string;
  subTitle: string | JSX.Element;
  ctaText: string | null;
  onClickCTA?: () => void;
  toolTip?: string | JSX.Element | null;
  stepName: 'start-referring' | 'activate-account' | 'integrate-api' | 'commission-step';
}

export type ActivationStatesT =
  | null
  | 'activated'
  | 'rejected'
  | 'needs_clarification'
  | 'under_review'
  | 'instantly_activated'
  | 'activated_mcc_pending';

export type PartnerTypeT = 'reseller' | 'aggregator' | 'fully_managed' | 'pure_platform';

export type AddMerchantSource =
  | 'activation-guide'
  | 'referral-guide'
  | 'referral-guide-pg'
  | 'referral-guide-x'
  | 'daily-earning';

export interface ProductListItemT {
  icon: string;
  onClickCTA: () => void;
  ctaText: string;
  title: string;
  subTitle: string | JSX.Element;
}

interface openModalArgs {
  size: string;
  component: JSX.Element;
}
export type OpenModalT = (arg: openModalArgs) => void;

interface showNotificationArgs {
  type: 'error' | 'success';
  message: string;
  hidePrevious?: boolean;
}
export type ShowNotificationT = (arg: showNotificationArgs) => void;

export interface PartnerHomeT {
  user: any;
  showNotification: ShowNotificationT;
  openModal: OpenModalT;
  closeModal: () => void;
}

export interface RTrackingT<P = Record<string, unknown>> {
  /**
   * This function tracks an event, along with related data.
   */
  trackEvent(data: Partial<P>): void;
}
