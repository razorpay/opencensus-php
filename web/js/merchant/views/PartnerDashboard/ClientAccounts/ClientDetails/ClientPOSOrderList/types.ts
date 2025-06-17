import { RazorpayUser as User } from '@libs/shared-types';
import { IconComponent } from '@razorpay/blade/components';

export type ProductPlans = 'lifetime' | 'monthly' | 'free';
export type PricingBreakupkeys = 'monthly' | 'lifetime' | 'setup_fee';
export type PricingTypes = 'monthly' | 'lifetime' | 'free';
export type CartItem = {
  code: string;
  quantity: number;
  plan: ProductPlans;
};
export type Gallery = {
  thumbnail: string;
  main: string;
  mobile: string;
};
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

export type LinkedItem = {
  title: string;
  offerLabel: string;
  quantity: number;
  image: any;
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

export type ProductDescription = {
  gallery: Gallery[];
  code: string;
  name: string;
  productTitle: string;
  description: string;
  cartImage: string;
  pricing: ProductDescriptionPricing[];
  isPartnerPricing: boolean;
  maxOrder: number | null;
  featureGallery: FeatureGallery[];
  infoBanner: InfoBanner;
  technicalSpecifications: TechnicalSpecification[];
  offer: {
    offerText: string;
    pdpOfferText: string;
    partnerOfferText: string;
    partnerPdpOfferText: string;
  } | null;
  shouldShowProductVarietyTable?: boolean;
  linkedItems?: LinkedItem[];
  maxQuantityErrMsg?: (quantity: number | null) => string;
  rentalDiscountPeriod: number;
};
export type CheckoutValidationError = {
  type: string;
  sev: 0 | 1 | 2;
  isHideCheckout: boolean;
  title?: string;
  description: string;
};

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
  isSoundboxEnabled?: boolean;
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

export interface ApiResponse<T> {
  status_code: number;
  success: boolean;
  data?: T;
  errors?: string[];
}
export type PlanType = {
  one_time_charge: number;
  plan_name: PricingTypes;
  rental_charges: number;
  setup_fee: number;
};
export type RateConfig = {
  advanced_rental_periods: number;
  rental_discount_periods: number;
  paper_roll_charges: number;
  plans: PlanType[];
};
export type DeviceMetaData = {
  charge_collection_product_id: string;
  rate_config_v2?: RateConfig | null;
};
export type ProductPricingMap = {
  name: string;
  code: string;
  entity_type?: string;
  rate_config: {
    monthly: number;
    lifetime: number;
    setup_fee: number;
  };
  metadata?: DeviceMetaData;
}[];
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

export type OrderStatusMetaData = {
  key: OrderStatusTypes;
  name: string;
  icon: IconComponent;
  variant: 'positive' | 'negative' | 'notice';
};

export interface OrderStatusData extends OrderStatusMetaData {
  statusTitle: string;
  statusDate: number;
}

export type OrderItem = {
  code: string;
  quantity: number;
  plan: ProductPlans;
};

export type getCartItemTotalArgs = {
  pricing: ProductDescriptionPricing[];
  quantity: number;
  selectedPlan: ProductPlans;
  isConsiderPrevValue?: boolean;
};

export type getCartItemTotalReturnType = {
  value: number;
  prevValue: number;
};

export type GetProductFromProductDescriptions = {
  code: string;
  productDescriptions: ProductDescription[];
};

export type ConstructProductDescription = {
  pricingPlanDict: ProductPricingMap;
  offerConfig?: Record<string, OfferConfig> | null;
  isSoundboxEnabled?: boolean;
};
export type GetFilteredPricing = {
  deviceConfig: {
    name: string;
    code: string;
    entity_type?: string;
    rate_config: {
      monthly: number;
      lifetime: number;
      setup_fee: number;
    };
    metadata?: DeviceMetaData;
  };
  pricing: ProductDescriptionPricing[];
};
export type GetProductDescriptionWithPricingPlan = {
  productCode: string;
  pricingPlanDict: ProductPricingMap;
  offerConfigForProduct?: OfferConfig | null;
};

export type GetPricingValues = {
  rateConfig: RateConfig;
  offerConfig?: OfferConfig | null;
  planType: PricingTypes;
  breakupKey: PricingBreakupkeys;
};

export type GetPricingValuesReturnType = {
  value: number;
  prevValue: number | null;
  nextValue: number;
};

export type FetchProductOffers = {
  isEnabled: boolean;
  offers: Record<string, OfferConfig> | null;
};
