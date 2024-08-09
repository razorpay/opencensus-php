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
  PAYMENT_METHODS = 'vasForm',
  NACH_FORM = 'nachForm',
}

export type OnboardingStepType =
  | AvailableSteps.MERCHANT_REGISTRATION
  | AvailableSteps.MERCHANT_KYC
  | AvailableSteps.DEVICE_SELECTION
  | AvailableSteps.PAYMENT_METHODS
  | AvailableSteps.AGREEMENT_SIGNING
  | AvailableSteps.ADDITIONAL_DETAILS;

export type OnboardingComponentType =
  | AvailableComponents.MOBILE_NUMBER_VERIFY
  | AvailableComponents.MERCHANT_KYC_REDIRECT
  | AvailableComponents.DEVICE_CART
  | AvailableComponents.DEVICE_SELECTION_CATALOG
  | AvailableComponents.AGREEMENT_SIGNING
  | AvailableComponents.ADDITIONAL_DETAILS
  | AvailableComponents.DEVICE_DELIVERY_ADDRESS
  | AvailableComponents.DEVICE_PAYMENT
  | AvailableComponents.PAYMENT_METHODS
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
  | 'underReview';

declare global {
  interface Window {
    cdnBaseUrl: string;
  }
}
