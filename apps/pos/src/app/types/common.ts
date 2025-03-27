import { DevicePaymentStatus } from "apps/pos/src/app/types/DeviceSelection";
import { PricingNcStatus } from "apps/pos/src/app/types/PaymentsAndService";
import { STATUS_FILTERS } from "apps/pos/src/app/types/SalesAssistedOnboarding";

export type APIResponse<SuccessReseponse, ErrorResponse> = {
  status_code: number;
  success: boolean;
  data?: SuccessReseponse;
  errors?: ErrorResponse[];
  code?: string;
};

export interface GraphQLErrorResponseType {
  __typename?: string;
  code: number;
  success: boolean;
  message: string;
}

export interface GraphResponse<Key extends string, SuccessResponse> {
  data: Record<
    Key,
    (SuccessResponse & Partial<Record<'__typename', string>>) | GraphQLErrorResponseType
  > | null;
  errors?: unknown;
}

export enum AvailableSteps {
  MERCHANT_REGISTRATION = 'merchantRegistration',
  MERCHANT_KYC = 'merchantKyc',
  DEVICE_SELECTION = 'deviceSelection',
  PAYMENT_METHODS = 'paymentMethods',
  AGREEMENT_SIGNING = 'agreementSigning',
  ADDITIONAL_DETAILS = 'additionalDetails',
  DEVICE_DEPLOYMENT = 'deviceDeployment',
}

export enum AvailableComponents {
  MOBILE_NUMBER_VERIFY = 'mobileNumberVerify',
  MERCHANT_KYC_REDIRECT = 'merchantKycRedirect',
  DEVICE_CART = 'deviceCart',
  DEVICE_SELECTION_CATALOG = 'deviceSelectionCatalog',
  AGREEMENT_SIGNING = 'agreementMode',
  ADDITIONAL_DETAILS = 'merchantAdditionalDetails',
  DEVICE_DELIVERY_ADDRESS = 'deviceDeliveryAddress',
  DEVICE_PAYMENT = 'devicePayment',
  DEVICE_DEPLOYMENT_LIST = 'deviceDeploymentList',
  DEVICE_TESTING = 'deviceTesting',
  DEVICE_CONFIGURATION = 'deviceConfiguration',
  LANGUAGE_CONFIGURATION = 'languageConfiguration',
  WIFI_CONFIGURATION = 'wifiConfiguration',
  DEVICE_DETAILS = 'deviceDetails',
  DEVICE_MAPPING_SCANNER = 'deviceMappingScanner',
  DEVICE_MAPPING_MANUAL = 'deviceMappingManual',
  PAYMENT_METHODS = 'vasForm',
  NACH_FORM = 'nachForm',
  BRAND_EMI_FORM = 'brandEmiForm',
  ADDED_BRAND_INFO = 'addedBrandInfo',
  DEVICE_MAPPING_SUCCESS = 'deviceMappingSuccess',
  DEVICE_PAYMENT_METHODS = 'devicePaymentMethods',
  PAYMENT_LINK_METHOD = 'paymentLink',
  SCAN_AND_PAY_METHOD = 'scanAndPay',
}

export type OnboardingStepType =
  | AvailableSteps.MERCHANT_REGISTRATION
  | AvailableSteps.MERCHANT_KYC
  | AvailableSteps.DEVICE_SELECTION
  | AvailableSteps.PAYMENT_METHODS
  | AvailableSteps.AGREEMENT_SIGNING
  | AvailableSteps.ADDITIONAL_DETAILS
  | AvailableSteps.DEVICE_DEPLOYMENT;

export type OnboardingComponentType =
  | AvailableComponents.MOBILE_NUMBER_VERIFY
  | AvailableComponents.MERCHANT_KYC_REDIRECT
  | AvailableComponents.DEVICE_CART
  | AvailableComponents.DEVICE_SELECTION_CATALOG
  | AvailableComponents.AGREEMENT_SIGNING
  | AvailableComponents.ADDITIONAL_DETAILS
  | AvailableComponents.DEVICE_DELIVERY_ADDRESS
  | AvailableComponents.DEVICE_PAYMENT_METHODS
  | AvailableComponents.PAYMENT_LINK_METHOD
  | AvailableComponents.SCAN_AND_PAY_METHOD
  | AvailableComponents.DEVICE_PAYMENT
  | AvailableComponents.DEVICE_DEPLOYMENT_LIST
  | AvailableComponents.DEVICE_TESTING
  | AvailableComponents.DEVICE_CONFIGURATION
  | AvailableComponents.LANGUAGE_CONFIGURATION
  | AvailableComponents.WIFI_CONFIGURATION
  | AvailableComponents.DEVICE_DETAILS
  | AvailableComponents.DEVICE_MAPPING_SCANNER
  | AvailableComponents.DEVICE_MAPPING_MANUAL
  | AvailableComponents.DEVICE_MAPPING_SUCCESS
  | AvailableComponents.PAYMENT_METHODS
  | AvailableComponents.BRAND_EMI_FORM
  | AvailableComponents.ADDED_BRAND_INFO
  | AvailableComponents.NACH_FORM;

export type RouteConfig = {
  fallback: string;
  child: {
    route: string;
    view: JSX.Element;
  }[];
};

export type SelectDropdownOptions = {
  label: string;
  value: string;
};
export type ActivationStatusKeys =
  | 'activated'
  | 'rejected'
  | 'needsClarification'
  | 'kycQualifiedStb'
  | 'pending'
  | 'underReview'
  | 'pricingNeedsClarification';

export enum MODULES {
  SALES_DASHBOARD = 'Sales Dashboard',
  MERCHANT_REGISTRATION = 'Merchant Registration',
  MERCHANT_KYC = 'Merchant Kyc',
  DEVICE_SELECTION = 'Device Selection',
  DEVICE_ADDRESS = 'Device Address',
  DEVICE_PAYMENT = 'Device Payment',
  PAYMENT_METHODS = 'Payment Methods',
  AGREEMENT_SIGNING = 'Agreement Signing',
  ADDITIONAL_DETAILS = 'Additional Details',
  DEVICE_DEPLOYMENT = 'Device Deployment',
}

export type AllBadgeTypes = DevicePaymentStatus | PricingNcStatus| STATUS_FILTERS | 'completed' | 'kyc_completed';