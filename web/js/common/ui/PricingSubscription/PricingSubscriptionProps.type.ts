import { TogglePlanValue } from 'common/ui/PricingSubscription/PricingBundleCommon';
import { PAYMENT_TYPE } from 'common/ui/PricingSubscription/constants';

interface pricingBundleAsset {
  pricingPlans?: Array<Record<string, string>> | [];
  featureIdOrder?: Array<string> | [];
  id?: string;
  featureIdToFeatureCopyMap?: Record<string, string>;
  heroImage?: { alt: string; src: string };
  header?: {
    pillText: string;
    title: string;
    icon: { alt: string; src: string };
  };
  tracking_data?: Record<string, string>;
}
interface PlansType {
  icon: {
    src: string;
    alt: string;
  };
  title: string;
  isRecommended: string;
  id: string;
  description: string;
  monthlyPrice: number;
  annualPrice: number;
  button: {
    variant: 'primary' | 'secondary' | 'tertiary' | undefined;
    label: string;
  };
}
interface CurrentBalanceContext {
  currentBalance: { data: { balance: number } };
  isLoading: boolean;
}
interface MultiPaymentContextType {
  multiPaymentData: {
    planName: string;
    amount: number;
    frequency: string;
    taxPercentage: number;
    icon: string;
  };
  currentBalance: { data: { balance: number } };
  plans: PlansType;
  checkoutPayment: checkoutPaymentType;
}
interface PricingSubscriptionProps extends CurrentBalanceContext {
  pricingSubscription: pricingBundleAsset;
  closeModal: () => void;
  showNotificationToast: ({
    type,
    message,
    closeTimeout,
  }: {
    type: string;
    message: string | any;
    closeTimeout?: number;
  }) => void;
  user: {
    current: string;
    isAllowedMultiple: (string) => boolean;
    isAccountAndSettingsRevampEnabled: boolean;
  };
  history;
  variant: string;
  templateId: string;
  fetchGSModal: ({ template_id }: { template_id: string }) => void;
  loading: boolean;
  gs_modals: pricingBundleAsset;
  isMobile: boolean;
}
interface FooterButtonType {
  isFullView: boolean;
  handleToggle: () => void;
  handleClose: (buttonType?: string) => void;
  equalizeRowElementHeights: () => void;
}

type TogglePlan = keyof typeof TogglePlanValue;

interface ViewMoreParams {
  text: string;
  pricingPlans: Array<PlansType>;
  featureId: string;
  featureIndex: number;
  handleMouseEnter: (title: string) => void;
  handleMouseLeave: (title: string) => void;
  togglePlan: TogglePlan;
}
interface PricingHeaderType {
  headerSrc: string;
  headerAlt: string;
  title: string;
  toggleSwitchButton: () => void;
  pillText: string;
  handleClose: (buttonType?: string) => void;
}
interface GetPlanPriceType {
  plans: PlansType;
  togglePlan: string;
  selectedPlanId: string;
  isReadOnly: boolean;
  isLoading: boolean;
  getPaymentMethodCall: (
    plans,
  ) => (plans?: PlansType | React.MouseEvent<HTMLButtonElement, MouseEvent>) => Promise<void>;
  isPaymentOptionLoading: boolean;
  isFullView: boolean;
  featureIndex: number;
  handleMouseEnter: (title: string) => void;
  handleMouseLeave: (title: string) => void;
  featureIdOrder: Array<string>;
  featureIdToFeatureCopyMap: { [key: string]: string };
  columnRefs: React.MutableRefObject<any[]>;
}

interface TncModalText {
  toggleTncModal: () => void;
}
interface TncModal extends TncModalText {
  isOpenTncModal: boolean;
}
interface TrackingObjectType {
  toggle_switch?: string;
  cta_value?: string;
  section?: string;
  type?: string;
  value?: string;
  event_name?: string;
  plan_Activated?: string;
  response_code?: string;
  payment_id?: string;
  plan_id?: string;
  time_spent?: any;
  checkout_id?: string;
  icon_type?: string;
  plan_viewed?: string;
  last_plan_id?: string;
  last_plan_viewed?: string;
  event_method?: string;
  plan_name?: string;
  payment_method?: string;
  modal?: string;
  balance?: string;
}
interface checkoutPaymentType {
  trackInstrumentation: (type: string, trackingObject: TrackingObjectType) => void;
  togglePlan: TogglePlan;
  setLoading: (value: React.SetStateAction<boolean>) => void;
  isLoading?: boolean;
  setSelectedPlanId: (value: React.SetStateAction<string>) => void;
  handlePaymentSuccess: (response: { razorpay_payment_id?: string }, plans: PlansType) => void;
  handlePaymentFailure: (
    response: { error?: { code: string; metadata?: { payment_id: string } } },
    plans: PlansType,
  ) => void;
  showNotificationToast: ({
    type,
    message,
    closeTimeout,
  }: {
    type: string;
    message: any;
    closeTimeout?: number | undefined;
  }) => void;
  planAmount: number;
  settlementBalance: number;
  setCongratulatoryModal: (flag: boolean) => void;
  setModalType: (modalType: string) => void;
  togglePaymentOptionModal: () => void;
  closeModal: () => void;
  currentBalance?: { data: { balance: number } };
}
interface PaymentCheckoutFlowType extends checkoutPaymentType {
  plans: PlansType;
  type: typeof PAYMENT_TYPE.INTERNAL | typeof PAYMENT_TYPE.PG;
}
type PaymentType = (typeof PAYMENT_TYPE)[keyof typeof PAYMENT_TYPE];

export type {
  PricingSubscriptionProps,
  FooterButtonType,
  GetPlanPriceType,
  PricingHeaderType,
  PlansType,
  ViewMoreParams,
  TogglePlan,
  TncModal,
  TncModalText,
  TrackingObjectType,
  PaymentCheckoutFlowType,
  pricingBundleAsset,
  CurrentBalanceContext,
  MultiPaymentContextType,
  checkoutPaymentType,
  PaymentType,
};
