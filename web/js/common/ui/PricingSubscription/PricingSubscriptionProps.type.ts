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
  showNotificationToast: ({ type, message }: { type: string; message: string | any }) => void;
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
}
interface FooterButtonType {
  isFullView: boolean;
  handleToggle: () => void;
  handleClose: (buttonType?: string | undefined) => () => void;
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
    plans: PlansType,
  ) => (plans?: PlansType | React.MouseEvent<HTMLButtonElement, MouseEvent>) => Promise<void>;
}
export {
  PricingSubscriptionProps,
  FooterButtonType,
  GetPlanPriceType,
  PricingHeaderType,
  PlansType,
};
