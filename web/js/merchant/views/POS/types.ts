import { IconComponent } from '@razorpay/blade/components';

import { User } from 'common/typings';

export type PricingTypes = 'monthly' | 'lifetime';

export type Gallery = {
  thumbnail: string;
  main: string;
  mobile: string;
};

export type PricingBreakupkeys = 'monthly' | 'lifetime' | 'setup_fee';

export type PricingBreakup = {
  key: PricingBreakupkeys;
  description: string;
  value: number;
  suffix: string;
  isExtraFee: boolean;
  isChargeableAtCheckout: boolean;
  prevValue: number | null;
  nextValue: number | null;
};

export type ProductDescriptionPricing = {
  name: string;
  type: PricingTypes;
  subText: string;
  breakups: PricingBreakup[];
};

export type FeatureGallery = {
  image: string;
  title: string;
  description: string;
  isImageFirst: boolean;
};

export type InfoBanner = {
  image: string;
  mobileImage: string;
  features: {
    icon: string;
    text: string;
  }[];
};

export type TechnicalSpecification = {
  category: string;
  value: string;
};

export type ProductDescription = {
  gallery: Gallery[];
  code: string;
  name: string;
  productTitle: string;
  description: string;
  cartImage: string;
  pricing: ProductDescriptionPricing[];
  isPartnerPricing: boolean;
  maxOrder: number;
  featureGallery: FeatureGallery[];
  infoBanner: InfoBanner;
  technicalSpecifications: TechnicalSpecification[];
  offer: {
    offerText: string;
    pdpOfferText: string;
    partnerOfferText: string;
    partnerPdpOfferText: string;
  } | null;
};

export type Features =
  | 'pricing_plan'
  | 'upi'
  | 'tapAndPay'
  | 'barcodeScanner'
  | 'printer'
  | 'screenSize'
  | 'standalone'
  | 'intergrationCapabilities'
  | 'nfc'
  | 'battery';

export type ProductFeaturesColumn = {
  key: Features;
  name: string;
  icon: string;
  boxSize: 'small' | 'medium' | 'large' | 'xlarge';
};

export type Feature = {
  isAvailable: boolean;
  name: string;
  isCustomComponent?: boolean;
  data?: unknown;
};

export type ProductTableProduct = {
  image: string;
  name: string;
  code: string;
  productTitle: string;
  features: Record<Features, Feature>;
};

export type ProductTableList = {
  features: ProductFeaturesColumn[][];
  products: ProductTableProduct[];
};

export type ProductPlans = 'lifetime' | 'monthly';

export type CartItem = {
  code: string;
  quantity: number;
  plan: ProductPlans;
};

export type ProductUpdateTypes = 'add' | 'reduce';

export type PricingPlanDict = {
  productName: string;
  monthly_sub: number;
  setup_fee: number;
  lifetime: number;
};

export type Product = {
  productCode: string;
  plan: ProductPlans;
};

export type UpdateCartTypes =
  | 'ADD_TO_CART'
  | 'INCREASE_QUANTITY'
  | 'DECREASE_QUANTITY'
  | 'REMOVE_ITEM'
  | 'TOGGLE_PLAN';

export type PosDeviceStoreState = {
  isCartOpen: boolean;
  cartItems: CartItem[];
  isPricingPlanLoading: boolean;
  productDescriptions: ProductDescription[];
  user: User | null;
  deliveryAddresses: DeliveryAddress[];
  checkoutErrors: CheckoutValidationError[];
  isDeliveryAddressFormOpen: boolean;
  // Note: We need isRenderedFromPartnerRoute to identify if the POS feature(such as OrderList) is
  // rendered for Partners to view their submerchant's orders
  isRenderedFromPartnerRoute: boolean;
};

export type RoutePattern = {
  path: string;
  steps: {
    link: ((args) => string) | string;
    label: ((args) => string) | string;
  }[];
};

export type OrderItem = {
  code: string;
  quantity: number;
  plan: ProductPlans;
};

export type DeliveryAddress = {
  id: string;
  name: string;
  phoneNumber: string;
  address: string;
  pincode: string;
  city: string;
  state: string;
  isSelected: boolean;
  type: string;
};

export type UpdateDeliveryAddress = {
  id: string;
  address: Omit<DeliveryAddress, 'id' | 'isSelected'>;
  isNewDeliveryAddress: boolean;
};

export type PosDeviceStoreActionType = {
  type: string;
  payload?: {
    cartItems?: CartItem[];
    productDescriptions?: ProductDescription[];
    user?: User;
    deliveryAddresses?: DeliveryAddress[];
    checkoutErrors?: CheckoutValidationError[];
    isDeliveryAddressFormOpen?: boolean;
  };
};

export type OrderListItem = {
  id: string;
  created_at: number;
  total_amount: number;
  status: string;
  arriving_at: number;
  quantity: number;
  invoice_url: string;
  items: OrderItem[];
};

export type CheckoutValidationError = {
  type: string;
  sev: 0 | 1 | 2;
  isHideCheckout: boolean;
  title?: string;
  description: string;
};

export interface OrderCreateResponse extends CreateOrderPayload {
  amount: {
    base: number;
    gst: number;
    total: number;
  };
  rental_amount: {
    base: number;
    gst: number;
    total: number;
  } | null;
}

export type OrderObjectType = {
  id: string;
  amount: number;
  amount_paid: number;
  amount_due: number;
  currency: string;
  status: string;
  created_at: number;
};

export type RefundStatusTypes = 'failed' | 'processing' | 'processed';
export interface OrderDetailsItem extends OrderCreateResponse {
  id: string;
  order_id: string;
  merchant_id: string;
  created_at: number;
  arriving_at: number;
  delivered_at: number;
  rejected_at: number;
  rejection_reasons: {
    error_code: string;
    error_description: string;
    error_reason: string;
  } | null;
  status: 'paid' | 'delivered' | 'rejected';
  order?: OrderObjectType | null;
  sales_code?: string;
  payment?: {
    amount: number;
    status: string;
    refund_status: null | string;
    captured: boolean;
    created_at: number;
  } | null;
  refund?: {
    id: string;
    amount: number;
    payment_id: string;
    created_at: number;
    status: RefundStatusTypes;
    acquirer_data: Record<string, string>;
  } | null;
}

export type OrderStatusTypes =
  | 'ORDER_RECEIVED'
  | 'ORDER_DELIVERED'
  | 'REFUND_PENDING'
  | 'REFUND_INITIATED'
  | 'REFUND_COMPLETED'
  | 'ORDER_REJECTED'
  | 'ORDER_CONFIRMED';

export type PincodeInfo = {
  state: string;
  city: string;
  state_code: string;
};

export type OrderStatusTimelineItem = {
  name: string;
  icon: IconComponent;
  variant: 'positive' | 'negative' | 'notice';
};

type OrderPricingOrderItem = {
  productDescription: ProductDescription;
  quantity: number;
  plan: ProductPlans;
  deviceTotal: number;
  rentalAmount: number | null;
  prevDeviceTotal: number;
  prevRetalAmount: number | null;
  nextRentalAmount: number | null;
};

export type OrderPricing = {
  deviceCharges: number;
  orderedDevices: OrderPricingOrderItem[];
  gstDevice: number;
  shipping: 'Free' | number;
  total: number;
  rentalCharges: number;
  rentalDevices: OrderPricingOrderItem[];
  gstRental: number;
  renewal: string;
  refund?: ProcessedRefundObj | null;
  invoiceUrl?: string | null;
  orderedDevicesWithOffer: OrderPricingOrderItem[];
  rentalDevicesWithOffer: OrderPricingOrderItem[];
  isPartnerPricing?: boolean;
};

export type CreateOrderPayload = {
  delivery_address: {
    name: string;
    address: string;
    city: string;
    country: string;
    state: string;
    pin_code: string;
    phone_no: string;
  };
  items: {
    code: string;
    count: number;
    period: string;
  }[];
};

export type ProcessedRefundObj = {
  id: string;
  refId: string;
  amount: number;
  status: 'processed' | 'pending' | 'failed';
};

export type DetailedPricingModel = {
  title: string | null;
  banner?: 'offer' | 'info';
  rows: {
    name: string;
    value: string | number;
    prevValue?: string | number;
    text?: string;
    isOfferOnlyField?: boolean;
  }[];
};

export interface ApiResponse<T> {
  status_code: number;
  success: boolean;
  data?: T;
  errors?: string[];
}

export type ProductPricingMap = {
  name: string;
  code: string;
  entity_type?: string;
  rate_config: {
    monthly: number;
    lifetime: number;
    setup_fee: number;
  };
}[];

export type CommsStatus =
  | 'active'
  | 'pending'
  | 'processing'
  | 'notice'
  | 'failed'
  | 'informationRequired'
  | 'refund_pending'
  | 'dispatched';

export type CommsItem = {
  title: string;
  description: string | ((props: CustomCommsContent) => JSX.Element | null);
  cta:
    | {
        name: string;
        url: string | ((props: CustomCommsContent) => string);
        type: 'button' | 'link';
      }[]
    | null;
  status: CommsStatus;
};

export type CommsBannerItemVariants =
  | 'positive'
  | 'negative'
  | 'neutral'
  | 'information'
  | 'notice';

export type CommsOrderItem = {
  id: string;
  amount: {
    total: number;
  };
  refund: {
    amount: number;
    status: string;
  } | null;
};

export type CustomCommsContent = {
  user: User;
  order?: OrderDetailsItem;
  isMobileOrTablet: boolean;
};

export type OrderStatusMetaData = {
  key: OrderStatusTypes;
  name: string;
  icon: IconComponent;
  variant: 'positive' | 'negative' | 'notice';
};

export type DeviceConfig = {
  rzp_key: string;
  configs: ProductPricingMap;
};

export type MainBannerItemStyleProps = {
  top: string;
  initialZoom: string;
  finalZoom: string;
};

export type MainBannerTilesItem = {
  name: string;
  decription: string;
  image: string;
  styleProps: MainBannerItemStyleProps;
};

export type PosActivationStatusTypes =
  | 'activated'
  | 'under_review'
  | 'needs_clarification'
  | 'rejected'
  | 'kyc_qualified_stb'
  | 'kyc_qualified_unactivated'
  | null;

export type OfferPricing = {
  prevValue: number | null;
  currentValue: number;
};

export type OfferCardItem = {
  pricing: (pricing: ProductDescriptionPricing) => OfferPricing;
  text: string;
};

export type OfferCardsStruct = {
  [key in PricingTypes]: OfferCardItem[];
};

export type TncTypes = 'offer' | 'normal' | 'nonOffer';

export type TncObject = {
  tncType: TncTypes;
  text: string;
  subpoints?: string[];
};

export enum POS_ACTIVATION_STATUS {
  submitted = 'submitted',
  needs_clarification = 'needs_clarification',
  rejected = 'rejected',
  under_review = 'under_review',
  activated = 'activated',
  kyc_qualified_stb = 'kyc_qualified_stb',
  kyc_qualified_unactivated = 'kyc_qualified_unactivated',
}

export type OfferConfig = {
  offerText: string;
  pdpOfferText: string;
  partnerOfferText: string;
  partnerPdpOfferText: string;
  preRateConfig: {
    monthly: number;
    lifetime: number;
    setup_fee: number;
  };
  nextRateConfig: {
    monthly: number | null;
    lifetime: number | null;
    setup_fee: number | null;
  };
};

export type PosAgreementSignIds = {
  merchantId: string;
  tncId: string;
  privacyId: string;
  pricingId?: string;
};

type ConsentField = {
  type: string;
  templateId: string;
};

export type PosAgreementSignPayload = {
  terms_and_conditions_consent_field: ConsentField;
  privacy_consent_field: ConsentField;
  pricing_consent_field?: ConsentField;
};

export type WorkflowData = {
  id: string;
  milestones: [ModularOnboardingMilestone];
  progress: number;
  status: string;
};

export type ModularOnboardingMilestone = {
  name: string;
  status: string;
  can_submit: boolean;
  steps: [ModularOnboardingStep];
  progress: number;
  meta: { template: string; title: string };
};
export type ModularOnboardingStep = {
  name: string;
  progress: number;
  status: string;
  components: [ModularOnboardingStepComponent];
  meta: {
    icon: string;
    template: string;
    title: string;
    description: string;
  };
};

export type ModularOnboardingStepComponent = {
  name: string;
  progress: number;
  status: string;
  verification: string;
  meta: any;
  is_required: boolean;
  fields: any[];
};

export type WorkflowConfig = {
  success: boolean;
  workflow_data: WorkflowData;
  onboarding_state: {
    components: string[];
    milestones: string[];
    steps: string[];
  };
  onboarding_status: string;
  country_code: string;
  onboarding_type: string;
  merchant_type: string;
};
