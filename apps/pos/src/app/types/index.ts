export type RouteType = {
  [index: string]: {
    root: string;
    nested_routes?: RouteType;
  };
};

// enums for filters/Status/State
export enum PosActivationStatus {
  IN_PROGRESS = 'IN_PROGRESS',
  UNDER_REVIEW = 'UNDER_REVIEW',
  NEEDS_CLARIFICATION = 'NEEDS_CLARIFICATION',
  KYC_QUALIFIED = 'KYC_QUALIFIED',
  ACTIVATED = 'ACTIVATED',
  REJECTED = 'REJECTED',
}

export enum FeeCategory {
  STANDARD = 'STANDARD',
  CUSTOM = 'CUSTOM',
}

export enum BusinessModel {
  DIRECT = 'DIRECT',
  AGGREGATOR = 'AGGREGATOR',
}

export enum SearchBy {
  MERCHANT_NAME = 'MERCHANT_NAME',
  MOBILE_NUMBER = 'MOBILE_NUMBER',
  MID = 'MID',
}

export enum All {
  ALL = 'ALL',
}

export enum AllFilters {
  STATUS = 'STATUS',
  PRICING = 'PRICING',
  BUSINESS_MODEL = 'BUSINESS_MODEL',
  SEARCH_BY = 'SEARCH_BY',
  SEARCH_FIELD = 'SEARCH_FIELD',
}

export type StatusFilterDropDownActionType = PosActivationStatus | All;
export type PricingFilterDropDownActionType = FeeCategory | All;
export type BusinessModelFilterDropDownActionType = BusinessModel | All;

export type FilterStateType = {
  status: StatusFilterDropDownActionType;
  pricing: PricingFilterDropDownActionType;
  businessModel: BusinessModelFilterDropDownActionType;
  searchBy: SearchBy;
  searchField: string;
};

export type FilterDropDownActionType =
  | StatusFilterDropDownActionType
  | PricingFilterDropDownActionType
  | BusinessModelFilterDropDownActionType
  | SearchBy;

export type TableItem = {
  id: string;
  initiatedOn: Date;
  mId: string;
  merchantName: string;
  mobileNumber: string;
  businessModel: BusinessModel;
  pricing: FeeCategory;
  status: PosActivationStatus;
};

export type AllMerchantsStatusType = {
  status: PosActivationStatus;
  count: number;
};

// types for browser' device

export enum DeviceType {
  MOBILE = 'mobile',
  DESKTOP = 'desktop',
}

// types for merchant onboarding landing page steps
export type MerchantOnboardingStepType = {
  title: string;
  completed: boolean;
  cta_link: string;
  steps: Array<{ title: string }>;
};

// types for devices

export enum SetupFee {
  SETUP_FEE = 'setup_fee',
}

export type PricingBreakupkeys = DevicePlanType | SetupFee;

export type Gallery = {
  thumbnail: string;
  main: string;
  mobile: string;
};

export type PosDeviceDescriptionPricing = {
  name: string;
  type: string;
  subText: string;
  breakups: {
    key: PricingBreakupkeys;
    description: string;
    value: number;
    suffix: string;
    isExtraFee: boolean;
    isChargeableAtCheckout: boolean;
  }[];
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

export type PosDeviceDescription = {
  gallery: Gallery[];
  code: string;
  name: string;
  productTitle: string;
  description: string;
  cartImage: string;
  pricing: PosDeviceDescriptionPricing[];
  maxOrder: number;
  featureGallery: FeatureGallery[];
  infoBanner: InfoBanner;
  technicalSpecifications: TechnicalSpecification[];
};

export type POSDevices = Array<PosDeviceDescription>;

export type MerchantOnboardingSteps = Array<MerchantOnboardingStepType>;

// types for cart

export enum CartActions {
  ADD = 'ADD',
  DELETE = 'DELETE',
  EDIT = 'EDIT',
}

export enum DevicePlanType {
  MONTHLY = 'monthly',
  QUATERLY = 'quaterly',
  HALF_YEARLY = 'half_yearly',
  ANNUAL = 'annual',
  LIFETIME = 'lifetime',
}

export type DevicePricingBreakupType = {
  type: PricingBreakupkeys;
  category: FeeCategory;
  charges: number;
};

export type DevicePricingType = {
  type: DevicePlanType;
  pricing_breakup: Array<DevicePricingBreakupType>;
  is_advance_rental_enabled: boolean;
  is_purchase_paper_roll_enabled: boolean;
  advance_rental_months?: number;
};

export type CartDeviceType = {
  deviceDescription: PosDeviceDescription;
  count: number;
  devicePricing: DevicePricingType;
};

export type PosAppStateType = {
  selectedDevices: Array<CartDeviceType>;
  addDevice: (device: CartDeviceType) => void;
  removeDevice: (deviceIdx: number) => void;
  editDevice: (device: CartDeviceType, deviceIdx: number) => void;
};
