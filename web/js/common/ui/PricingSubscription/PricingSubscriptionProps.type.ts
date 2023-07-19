import { TogglePlanValue } from 'common/ui/PricingSubscription/PricingBundleCommon';

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
interface PricingSubscriptionProps {
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
  handleClose: (buttonType?: string | undefined) => () => void;
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
  isChecked: boolean;
  toggleSwitchButton: (e: React.FormEvent<HTMLInputElement>) => void;
  pillText: string;
  handleClose: (buttonType?: string) => void;
}
interface GetPlanPriceType {
  plans: PlansType;
  togglePlan: string;
  selectedPlanId: string;
  isReadOnly: boolean;
  isLoading: boolean;
  handleCheckoutPayment: (
    props: PaymentCheckoutFlowType,
  ) => (plans?: PlansType | React.MouseEvent<HTMLButtonElement, MouseEvent>) => Promise<void>;
  checkoutPayment: PaymentCheckoutFlowType;
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
}
interface PaymentCheckoutFlowType {
  plans: PlansType;
  trackInstrumentation: (type: string, trackingObject: TrackingObjectType) => void;
  togglePlan: TogglePlan;
  setLoading: (value: React.SetStateAction<boolean>) => void;
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
}

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
};
