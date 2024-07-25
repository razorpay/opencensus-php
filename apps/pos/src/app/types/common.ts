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
}

export enum AvailableComponents {
  MOBILE_NUMBER_VERIFY = 'mobileNumberVerify',
  MERCHANT_KYC_REDIRECT = 'merchantKycRedirect',
}

export type OnboardingStepType =
  | AvailableSteps.MERCHANT_REGISTRATION
  | AvailableSteps.MERCHANT_KYC
  | AvailableSteps.DEVICE_SELECTION;

export type OnboardingComponentType =
  | AvailableComponents.MOBILE_NUMBER_VERIFY
  | AvailableComponents.MERCHANT_KYC_REDIRECT;

export type RouteConfig = {
  fallback: string;
  child: {
    route: string;
    view: JSX.Element;
  }[];
};
