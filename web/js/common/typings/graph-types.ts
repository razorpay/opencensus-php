import { GraphQLResolveInfo, GraphQLScalarType, GraphQLScalarTypeConfig } from 'graphql';
export type Maybe<T> = T | null;
export type InputMaybe<T> = Maybe<T>;
export type Exact<T extends { [key: string]: unknown }> = { [K in keyof T]: T[K] };
export type MakeOptional<T, K extends keyof T> = Omit<T, K> & { [SubKey in K]?: Maybe<T[SubKey]> };
export type MakeMaybe<T, K extends keyof T> = Omit<T, K> & { [SubKey in K]: Maybe<T[SubKey]> };
export type Omit<T, K extends keyof T> = Pick<T, Exclude<keyof T, K>>;
export type RequireFields<T, K extends keyof T> = Omit<T, K> & { [P in K]-?: NonNullable<T[P]> };
/** All built-in and custom scalars, mapped to their actual values */
export type Scalars = {
  ID: string;
  String: string;
  Boolean: boolean;
  Int: number;
  Float: number;
  BigInt: number;
  DateTime: Date;
  EmailAddress: string;
  JSON: { [key: string]: any };
  JSONObject: { [key: string]: any };
  NonNegativeInt: number;
  PositiveInt: number;
  URL: string;
  Upload: any;
  VPA: any;
};

export enum AadhaarCaptchaErrorTypeEnum {
  EMPTY_RESPONSE = 'EMPTY_RESPONSE',
  INVALID_RESPONSE = 'INVALID_RESPONSE',
  NO_PROVIDER_ERROR = 'NO_PROVIDER_ERROR',
  TIME_OUT_ERROR = 'TIME_OUT_ERROR',
  UIDAI_DOWNTIME_ERROR = 'UIDAI_DOWNTIME_ERROR',
}

export type AadhaarCaptchaResponse = {
  __typename?: 'AadhaarCaptchaResponse';
  captcha: Scalars['String'];
  isSessionExpired: Scalars['Boolean'];
};

export type AadhaarCaptchaV2FailureResponse = {
  __typename?: 'AadhaarCaptchaV2FailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type AadhaarCaptchaV2Response =
  | AadhaarCaptchaV2FailureResponse
  | AadhaarCaptchaV2SuccessResponse;

export type AadhaarCaptchaV2SuccessResponse = {
  __typename?: 'AadhaarCaptchaV2SuccessResponse';
  captcha: Scalars['String'];
  code: Scalars['PositiveInt'];
  isSessionExpired: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum AadhaarCaptchaVerifyErrorTypeEnum {
  EMPTY_RESPONSE = 'EMPTY_RESPONSE',
  INVALID_AADHAAR_NUMBER = 'INVALID_AADHAAR_NUMBER',
  INVALID_CAPTCHA = 'INVALID_CAPTCHA',
  INVALID_RESPONSE = 'INVALID_RESPONSE',
  INVALID_SESSION_ID = 'INVALID_SESSION_ID',
  MOBILE_NOT_LINKED = 'MOBILE_NOT_LINKED',
  NO_PROVIDER_ERROR = 'NO_PROVIDER_ERROR',
  TIME_OUT_ERROR = 'TIME_OUT_ERROR',
  UIDAI_DOWNTIME_ERROR = 'UIDAI_DOWNTIME_ERROR',
  VALID_RESPONSE = 'VALID_RESPONSE',
}

export type AadhaarCaptchaVerifyResponse = MutationResponseInterface & {
  __typename?: 'AadhaarCaptchaVerifyResponse';
  code: Scalars['PositiveInt'];
  errorCode?: Maybe<AadhaarCaptchaVerifyErrorTypeEnum>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type AadhaarDigilockerOtpFailureResponse = {
  __typename?: 'AadhaarDigilockerOtpFailureResponse';
  code: Scalars['PositiveInt'];
  errorCode: AadhaarOtpDigilockerErrorEnum;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type AadhaarDigilockerOtpResponse =
  | AadhaarDigilockerOtpFailureResponse
  | AadhaarDigilockerOtpSuccessResponse;

export type AadhaarDigilockerOtpSuccessResponse = {
  __typename?: 'AadhaarDigilockerOtpSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  requestId: Scalars['ID'];
  success: Scalars['Boolean'];
};

export type AadhaarDigilockerOtpVerifyFailureResponse = {
  __typename?: 'AadhaarDigilockerOtpVerifyFailureResponse';
  code: Scalars['PositiveInt'];
  errorCode: AadhaarOtpDigilockerErrorEnum;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type AadhaarDigilockerOtpVerifyResponse =
  | AadhaarDigilockerOtpVerifyFailureResponse
  | AadhaarDigilockerOtpVerifySuccessResponse;

export type AadhaarDigilockerOtpVerifySuccessResponse = {
  __typename?: 'AadhaarDigilockerOtpVerifySuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum AadhaarDigilockerRedirectionUrlErrorTypeEnum {
  BAD_REQUEST_ERROR = 'BAD_REQUEST_ERROR',
  EMPTY_RESPONSE = 'EMPTY_RESPONSE',
  EXTERNAL_SERVICE_ERROR = 'EXTERNAL_SERVICE_ERROR',
  SERVER_ERROR = 'SERVER_ERROR',
  TIME_OUT_ERROR = 'TIME_OUT_ERROR',
  VENDOR_NOT_AVAILABLE = 'VENDOR_NOT_AVAILABLE',
}

export type AadhaarDigilockerRedirectionUrlFailureResponse = {
  __typename?: 'AadhaarDigilockerRedirectionUrlFailureResponse';
  code: Scalars['PositiveInt'];
  errorCode: AadhaarDigilockerRedirectionUrlErrorTypeEnum;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type AadhaarDigilockerRedirectionUrlResponse =
  | AadhaarDigilockerRedirectionUrlFailureResponse
  | AadhaarDigilockerRedirectionUrlSuccessResponse;

export type AadhaarDigilockerRedirectionUrlSuccessResponse = {
  __typename?: 'AadhaarDigilockerRedirectionUrlSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  redirectionUrl: Scalars['String'];
  success: Scalars['Boolean'];
};

export type AadhaarDigilockerRedirectionUrlVerifyResponse =
  | AadhaarDigilockerRedirectionVerificationFailureResponse
  | AadhaarDigilockerRedirectionVerificationSuccessResponse;

export enum AadhaarDigilockerRedirectionVerificationErrorTypeEnum {
  BAD_REQUEST_ERROR = 'BAD_REQUEST_ERROR',
  EMPTY_RESPONSE = 'EMPTY_RESPONSE',
  EXTERNAL_SERVICE_ERROR = 'EXTERNAL_SERVICE_ERROR',
  SERVER_ERROR = 'SERVER_ERROR',
  TIME_OUT_ERROR = 'TIME_OUT_ERROR',
  VENDOR_NOT_AVAILABLE = 'VENDOR_NOT_AVAILABLE',
}

export type AadhaarDigilockerRedirectionVerificationFailureResponse = {
  __typename?: 'AadhaarDigilockerRedirectionVerificationFailureResponse';
  code: Scalars['PositiveInt'];
  errorCode: AadhaarDigilockerRedirectionVerificationErrorTypeEnum;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type AadhaarDigilockerRedirectionVerificationSuccessResponse = {
  __typename?: 'AadhaarDigilockerRedirectionVerificationSuccessResponse';
  code: Scalars['PositiveInt'];
  isValid: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum AadhaarOtpDigilockerErrorEnum {
  EMPTY_RESPONSE = 'EMPTY_RESPONSE',
  EXTERNAL_SERVICE_ERROR = 'EXTERNAL_SERVICE_ERROR',
  INCORRECT_OTP = 'INCORRECT_OTP',
  INVALID_INPUT_DETAILS = 'INVALID_INPUT_DETAILS',
  INVALID_RESPONSE = 'INVALID_RESPONSE',
  NO_PROVIDER_ERROR = 'NO_PROVIDER_ERROR',
}

export enum AadhaarOtpVerifyErrorTypeEnum {
  EMPTY_RESPONSE = 'EMPTY_RESPONSE',
  INCORRECT_OTP = 'INCORRECT_OTP',
  INVALID_RESPONSE = 'INVALID_RESPONSE',
  INVALID_SESSION_ID = 'INVALID_SESSION_ID',
  NO_PROVIDER_ERROR = 'NO_PROVIDER_ERROR',
  OTP_LIMIT_EXCEEDED = 'OTP_LIMIT_EXCEEDED',
  TIME_OUT_ERROR = 'TIME_OUT_ERROR',
  UIDAI_DOWNTIME_ERROR = 'UIDAI_DOWNTIME_ERROR',
  VALID_RESPONSE = 'VALID_RESPONSE',
}

export type AadhaarOtpVerifyResponse = MutationResponseInterface & {
  __typename?: 'AadhaarOtpVerifyResponse';
  code: Scalars['PositiveInt'];
  errorCode?: Maybe<AadhaarOtpVerifyErrorTypeEnum>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type AcceptPaymentsProduct = {
  __typename?: 'AcceptPaymentsProduct';
  description: Scalars['String'];
  isFtuxComplete: Scalars['Boolean'];
  isNewLaunch: Scalars['Boolean'];
  title: Scalars['String'];
  type: Scalars['String'];
};

export type AcceptPaymentsWidget = {
  __typename?: 'AcceptPaymentsWidget';
  products?: Maybe<Array<AcceptPaymentsProduct>>;
  title: Scalars['String'];
  type: PaymentsWidgetTypeEnum;
  variant: WidgetVariantEnum;
};

export type AccountVerificationOtpResendResponse = {
  __typename?: 'AccountVerificationOtpResendResponse';
  code: Scalars['PositiveInt'];
  errorCode?: Maybe<Scalars['String']>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token?: Maybe<Scalars['String']>;
};

export type AccountVerificationOtpResponse = {
  __typename?: 'AccountVerificationOtpResponse';
  code: Scalars['PositiveInt'];
  errorCode?: Maybe<Scalars['String']>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token?: Maybe<Scalars['String']>;
};

export type AcquirerData = {
  __typename?: 'AcquirerData';
  arn?: Maybe<Scalars['String']>;
  rrn?: Maybe<Scalars['String']>;
  utr?: Maybe<Scalars['String']>;
};

export type Address = {
  __typename?: 'Address';
  city?: Maybe<Scalars['String']>;
  country?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  isPrimary?: Maybe<Scalars['Boolean']>;
  line1?: Maybe<Scalars['String']>;
  line2?: Maybe<Scalars['String']>;
  state?: Maybe<Scalars['String']>;
  zipcode?: Maybe<Scalars['PositiveInt']>;
};

export type AddressByPincodeFailureResponse = {
  __typename?: 'AddressByPincodeFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type AddressByPincodeResponse =
  | AddressByPincodeFailureResponse
  | AddressByPincodeSuccessResponse;

export type AddressByPincodeSuccessResponse = {
  __typename?: 'AddressByPincodeSuccessResponse';
  city: Scalars['String'];
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  state: Scalars['String'];
  stateCode: Scalars['String'];
  success: Scalars['Boolean'];
};

export type AggregationResultType = {
  __typename?: 'AggregationResultType';
  status: Scalars['String'];
  value: Scalars['PositiveInt'];
};

export enum ApiKeyRegenerationDelayTypeEnum {
  DELAY = 'DELAY',
  NO_DELAY = 'NO_DELAY',
}

export type ApproveIciciPayoutResponse = MutationResponseInterface & {
  __typename?: 'ApproveIciciPayoutResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type ApprovePayoutBatchResponse =
  | ApprovePayoutBatchResponseFailure
  | ApprovePayoutBatchResponseSuccess;

export type ApprovePayoutBatchResponseFailure = {
  __typename?: 'ApprovePayoutBatchResponseFailure';
  code: Scalars['PositiveInt'];
  failedPayoutBatchIds?: Maybe<Array<Scalars['ID']>>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type ApprovePayoutBatchResponseSuccess = {
  __typename?: 'ApprovePayoutBatchResponseSuccess';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type ApprovePayoutResponse = MutationResponseInterface & {
  __typename?: 'ApprovePayoutResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type Auth = AuthUnauthenticated | AuthUnregistered | AuthUser;

export enum AuthErrorCodeEnum {
  BAD_REQUEST_2FA_LOGIN_INCORRECT_PASSWORD = 'BAD_REQUEST_2FA_LOGIN_INCORRECT_PASSWORD',
  BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED = 'BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED',
  BAD_REQUEST_INCORRECT_OTP = 'BAD_REQUEST_INCORRECT_OTP',
  BAD_REQUEST_LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED = 'BAD_REQUEST_LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED',
  BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED = 'BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED',
  BAD_REQUEST_OTP_LOGIN_LOCKED = 'BAD_REQUEST_OTP_LOGIN_LOCKED',
  BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED = 'BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED',
  BAD_REQUEST_VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED = 'BAD_REQUEST_VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED',
  LOGIN_UNAUTHENTICATED = 'LOGIN_UNAUTHENTICATED',
}

export enum AuthSourceEnum {
  EMAIL = 'EMAIL',
  PHONE = 'PHONE',
}

export type AuthUnauthenticated = {
  __typename?: 'AuthUnauthenticated';
  errorCode?: Maybe<AuthErrorCodeEnum>;
  message: Scalars['String'];
};

export type AuthUnregistered = {
  __typename?: 'AuthUnregistered';
  message: Scalars['String'];
};

export type AuthUser = MutationResponseInterface & {
  __typename?: 'AuthUser';
  code: Scalars['PositiveInt'];
  message: Scalars['String'];
  source?: Maybe<AuthSourceEnum>;
  success: Scalars['Boolean'];
  user: User;
};

export type Bank = {
  __typename?: 'Bank';
  address: Scalars['String'];
  branchName: Scalars['String'];
  centre: Scalars['String'];
  city: Scalars['String'];
  code: Scalars['String'];
  contactNumber: Phone;
  district: Scalars['String'];
  ifsc: Scalars['String'];
  isImpsEnabled: Scalars['Boolean'];
  isNeftEnabled: Scalars['Boolean'];
  isRtgsEnabled: Scalars['Boolean'];
  isUpiEnabled: Scalars['Boolean'];
  micrCode: Scalars['String'];
  name: Scalars['String'];
  state: Scalars['String'];
  swiftCode: Scalars['String'];
};

export type BusinessType = {
  __typename?: 'BusinessType';
  label: Scalars['String'];
  status: Scalars['String'];
  value: MerchantBusinessTypeEnum;
};

export enum CaptchaModeEnum {
  INVISIBLE = 'INVISIBLE',
  V3 = 'V3',
}

export type CheckoutOptions = {
  __typename?: 'CheckoutOptions';
  email?: Maybe<Scalars['String']>;
  phone?: Maybe<Scalars['String']>;
};

export type ClarificationComment = {
  __typename?: 'ClarificationComment';
  text: Scalars['String'];
  type: MerchantCommentTypeEnum;
};

export type ClarificationComments = {
  __typename?: 'ClarificationComments';
  comment?: Maybe<ClarificationComment>;
  createdAt: Scalars['PositiveInt'];
  fieldDetails?: Maybe<MerchantClarificationFieldValues>;
  messageFrom: MerchantClarificationFromEnum;
  ncCount: Scalars['Int'];
  status: MerchantClarificationStatusEnum;
};

export enum ClientPlatformEnum {
  ANDROID = 'ANDROID',
  DASHBOARD = 'DASHBOARD',
  EPOS = 'EPOS',
  IOS = 'IOS',
  X_ANDROID = 'X_ANDROID',
  X_IOS = 'X_IOS',
}

export type ConfigData = {
  __typename?: 'ConfigData';
  /** count of bvs bank verification attempt */
  bankAccountVerificationAttemptCount?: Maybe<Scalars['Int']>;
  /** count of company pan verification attempt */
  companyPanVerificationAttemptCount?: Maybe<Scalars['Int']>;
  couponPopupCount?: Maybe<Scalars['Int']>;
  /** Indicates visibility of mtu transaction Popup */
  isMtuCouponAvailable: Scalars['Boolean'];
  /** Indicates visibility of mtu congratulatory Popup */
  isMtuCouponCongratulatoryPopupEnabled: Scalars['Boolean'];
  /** Indicates whether merchant has signed up through referral and not yet done mtu */
  isSignedUpReferee?: Maybe<Scalars['Boolean']>;
  /** count of promoter pan verification attempt */
  promoterPanVerificationAttemptCount?: Maybe<Scalars['Int']>;
  /** Merchant names of friends who completed the referral process successfully since modal is last seen by advocate */
  refereeNames?: Maybe<Array<Maybe<Scalars['String']>>>;
  /** Amount credit (in paisa) to for each successful referral */
  referralAmount?: Maybe<Scalars['Int']>;
  /** Count of referral success popup - defaults to zero */
  referralSuccessPopupCount?: Maybe<Scalars['Int']>;
  /** Total referrals done by advocate so far */
  referredCount?: Maybe<Scalars['Int']>;
  /** Indicates visibility of ftux final screen */
  showFtuxFinalScreen?: Maybe<Scalars['Boolean']>;
  /** Indicates visibility of first time payment success banner for ftux */
  showFtuxFirstPaymentBanner?: Maybe<Scalars['Boolean']>;
  upiTerminalProcurementStatus?: Maybe<UpiTerminalProcurementStatusEnum>;
  /** Count of website compliance skips */
  websiteIncompleteSoftNudgeCount?: Maybe<Scalars['Int']>;
};

export type CouponApplyResponse = MutationResponseInterface & {
  __typename?: 'CouponApplyResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type CouponValidateResponse = MutationResponseInterface & {
  __typename?: 'CouponValidateResponse';
  code: Scalars['PositiveInt'];
  credits?: Maybe<Scalars['BigInt']>;
  expiryDays?: Maybe<Scalars['PositiveInt']>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type Currency = {
  __typename?: 'Currency';
  code: CurrencyCodeEnum;
  name?: Maybe<CurrencyNameEnum>;
};

export enum CurrencyCodeEnum {
  AED = 'AED',
  ALL = 'ALL',
  AMD = 'AMD',
  ARS = 'ARS',
  AUD = 'AUD',
  AWG = 'AWG',
  BBD = 'BBD',
  BDT = 'BDT',
  BMD = 'BMD',
  BND = 'BND',
  BOB = 'BOB',
  BSD = 'BSD',
  BWP = 'BWP',
  BZD = 'BZD',
  CAD = 'CAD',
  CHF = 'CHF',
  CNY = 'CNY',
  COP = 'COP',
  CRC = 'CRC',
  CUP = 'CUP',
  CZK = 'CZK',
  DKK = 'DKK',
  DOP = 'DOP',
  DZD = 'DZD',
  EGP = 'EGP',
  ETB = 'ETB',
  EUR = 'EUR',
  FJD = 'FJD',
  GBP = 'GBP',
  GHS = 'GHS',
  GIP = 'GIP',
  GMD = 'GMD',
  GTQ = 'GTQ',
  GYD = 'GYD',
  HKD = 'HKD',
  HNL = 'HNL',
  HRK = 'HRK',
  HTG = 'HTG',
  HUF = 'HUF',
  IDR = 'IDR',
  ILS = 'ILS',
  INR = 'INR',
  JMD = 'JMD',
  KES = 'KES',
  KGS = 'KGS',
  KHR = 'KHR',
  KYD = 'KYD',
  KZT = 'KZT',
  LAK = 'LAK',
  LBP = 'LBP',
  LKR = 'LKR',
  LRD = 'LRD',
  LSL = 'LSL',
  MAD = 'MAD',
  MDL = 'MDL',
  MKD = 'MKD',
  MMK = 'MMK',
  MNT = 'MNT',
  MOP = 'MOP',
  MUR = 'MUR',
  MVR = 'MVR',
  MWK = 'MWK',
  MXN = 'MXN',
  MYR = 'MYR',
  NAD = 'NAD',
  NGN = 'NGN',
  NIO = 'NIO',
  NOK = 'NOK',
  NPR = 'NPR',
  NZD = 'NZD',
  PEN = 'PEN',
  PGK = 'PGK',
  PHP = 'PHP',
  PKR = 'PKR',
  QAR = 'QAR',
  RUB = 'RUB',
  SAR = 'SAR',
  SCR = 'SCR',
  SEK = 'SEK',
  SGD = 'SGD',
  SLL = 'SLL',
  SOS = 'SOS',
  SSP = 'SSP',
  SVC = 'SVC',
  SZL = 'SZL',
  THB = 'THB',
  TTD = 'TTD',
  TZS = 'TZS',
  USD = 'USD',
  UYU = 'UYU',
  UZS = 'UZS',
  YER = 'YER',
  ZAR = 'ZAR',
}

export type CurrencyInput = {
  code: CurrencyCodeEnum;
  name?: InputMaybe<CurrencyNameEnum>;
};

export enum CurrencyNameEnum {
  ALBANIAN_LEKARMENIAN_DRAM = 'ALBANIAN_LEKARMENIAN_DRAM',
  ALGERIAN_DINAR = 'ALGERIAN_DINAR',
  ARGENTINE_PESO = 'ARGENTINE_PESO',
  ARUBAN_FLORIN = 'ARUBAN_FLORIN',
  AUSTRALIAN_DOLLAR = 'AUSTRALIAN_DOLLAR',
  BAHAMIAN_DOLLAR = 'BAHAMIAN_DOLLAR',
  BANGLADESHI_TAKA = 'BANGLADESHI_TAKA',
  BARBADIAN_DOLLAR = 'BARBADIAN_DOLLAR',
  BELIZE_DOLLAR = 'BELIZE_DOLLAR',
  BERMUDIAN_DOLLAR = 'BERMUDIAN_DOLLAR',
  BOLIVIAN_BOLIVIANO = 'BOLIVIAN_BOLIVIANO',
  BOTSWANA_PULA = 'BOTSWANA_PULA',
  BRUNEI_DOLLAR = 'BRUNEI_DOLLAR',
  CAMBODIAN_RIEL = 'CAMBODIAN_RIEL',
  CANADIAN_DOLLAR = 'CANADIAN_DOLLAR',
  CAYMAN_ISLANDS_DOLLAR = 'CAYMAN_ISLANDS_DOLLAR',
  CHINESE_YUAN_RENMINBI = 'CHINESE_YUAN_RENMINBI',
  COLOMBIAN_PESO = 'COLOMBIAN_PESO',
  COSTA_RICAN_COLON = 'COSTA_RICAN_COLON',
  CROATIAN_KUNA = 'CROATIAN_KUNA',
  CUBAN_PESO = 'CUBAN_PESO',
  CZECH_KORUNA = 'CZECH_KORUNA',
  DANISH_KRONE = 'DANISH_KRONE',
  DOMINICAN_PESO = 'DOMINICAN_PESO',
  EGYPTIAN_POUND = 'EGYPTIAN_POUND',
  ETHIOPIAN_BIRR = 'ETHIOPIAN_BIRR',
  EUROPEAN_EURO = 'EUROPEAN_EURO',
  FIJIAN_DOLLAR = 'FIJIAN_DOLLAR',
  GAMBIAN_DALASI = 'GAMBIAN_DALASI',
  GHANIAN_CEDI = 'GHANIAN_CEDI',
  GIBRALTAR_POUND = 'GIBRALTAR_POUND',
  GUATEMALAN_QUETZAL = 'GUATEMALAN_QUETZAL',
  GUYANESE_DOLLAR = 'GUYANESE_DOLLAR',
  HAITIAN_GOURDE = 'HAITIAN_GOURDE',
  HONDURAN_LEMPIRA = 'HONDURAN_LEMPIRA',
  HONG_KONG_DOLLAR = 'HONG_KONG_DOLLAR',
  HUNGARIAN_FORINT = 'HUNGARIAN_FORINT',
  INDIAN_RUPEE = 'INDIAN_RUPEE',
  INDONESIAN_RUPIAH = 'INDONESIAN_RUPIAH',
  ISRAELI_NEW_SHEKEL = 'ISRAELI_NEW_SHEKEL',
  JAMAICAN_DOLLAR = 'JAMAICAN_DOLLAR',
  KAZAKHSTANI_TENGE = 'KAZAKHSTANI_TENGE',
  KENYAN_SHILLING = 'KENYAN_SHILLING',
  KYRGYZSTANI_SOM = 'KYRGYZSTANI_SOM',
  LAO_KIP = 'LAO_KIP',
  LEBANESE_POUND = 'LEBANESE_POUND',
  LESOTHO_LOTI = 'LESOTHO_LOTI',
  LIBERIAN_DOLLAR = 'LIBERIAN_DOLLAR',
  MACANESE_PATACA = 'MACANESE_PATACA',
  MACEDONIAN_DENAR = 'MACEDONIAN_DENAR',
  MALAWIAN_KWACHA = 'MALAWIAN_KWACHA',
  MALAYSIAN_RINGGIT = 'MALAYSIAN_RINGGIT',
  MALDIVIAN_RUFIYAA = 'MALDIVIAN_RUFIYAA',
  MAURITIAN_RUPEE = 'MAURITIAN_RUPEE',
  MEXICAN_PESO = 'MEXICAN_PESO',
  MOLDOVAN_LEU = 'MOLDOVAN_LEU',
  MONGOLIAN_TUGRIK = 'MONGOLIAN_TUGRIK',
  MOROCCAN_DIRHAM = 'MOROCCAN_DIRHAM',
  MYANMAR_KYAT = 'MYANMAR_KYAT',
  NAMIBIAN_DOLLAR = 'NAMIBIAN_DOLLAR',
  NEPALESE_RUPEE = 'NEPALESE_RUPEE',
  NEW_ZEALAND_DOLLAR = 'NEW_ZEALAND_DOLLAR',
  NICARAGUAN_CORDOBA = 'NICARAGUAN_CORDOBA',
  NIGERIAN_NAIRA = 'NIGERIAN_NAIRA',
  NORWEGIAN_KRONE = 'NORWEGIAN_KRONE',
  PAKISTANI_RUPEE = 'PAKISTANI_RUPEE',
  PAPUA_NEW_GUINEAN_KINA = 'PAPUA_NEW_GUINEAN_KINA',
  PERUVIAN_SOL = 'PERUVIAN_SOL',
  PHILIPPINE_PESO = 'PHILIPPINE_PESO',
  POUND_STERLING = 'POUND_STERLING',
  QATARI_RIYAL = 'QATARI_RIYAL',
  RUSSIAN_RUBLE = 'RUSSIAN_RUBLE',
  SALVADORAN_COLON = 'SALVADORAN_COLON',
  SAUDI_ARABIAN_RIYAL = 'SAUDI_ARABIAN_RIYAL',
  SEYCHELLOIS_RUPEE = 'SEYCHELLOIS_RUPEE',
  SIERRA_LEONEAN_LEONE = 'SIERRA_LEONEAN_LEONE',
  SINGAPORE_DOLLAR = 'SINGAPORE_DOLLAR',
  SOMALI_SHILLING = 'SOMALI_SHILLING',
  SOUTH_AFRICAN_RAND = 'SOUTH_AFRICAN_RAND',
  SOUTH_SUDANESE_POUND = 'SOUTH_SUDANESE_POUND',
  SRI_LANKAN_RUPEE = 'SRI_LANKAN_RUPEE',
  SWAZI_LILANGENI = 'SWAZI_LILANGENI',
  SWEDISH_KRONA = 'SWEDISH_KRONA',
  SWISS_FRANC = 'SWISS_FRANC',
  TANZANIAN_SHILLING = 'TANZANIAN_SHILLING',
  THAI_BAHT = 'THAI_BAHT',
  TRINIDAD_AND_TOBAGO_DOLLAR = 'TRINIDAD_AND_TOBAGO_DOLLAR',
  UNITED_ARAB_EMIRATES_DIRHAM = 'UNITED_ARAB_EMIRATES_DIRHAM',
  UNITED_STATES_DOLLAR = 'UNITED_STATES_DOLLAR',
  URUGUAYAN_PESO = 'URUGUAYAN_PESO',
  UZBEKISTANI_SOM = 'UZBEKISTANI_SOM',
  YEMENI_RIAL = 'YEMENI_RIAL',
}

export type Customer = {
  __typename?: 'Customer';
  address?: Maybe<CustomerAddress>;
  email?: Maybe<Scalars['EmailAddress']>;
  id?: Maybe<Scalars['ID']>;
  name?: Maybe<Scalars['String']>;
  phone?: Maybe<Phone>;
};

export type CustomerAddress = {
  __typename?: 'CustomerAddress';
  billing?: Maybe<Address>;
  shipping?: Maybe<Address>;
};

export type CustomerInput = {
  email?: InputMaybe<Scalars['EmailAddress']>;
  id?: InputMaybe<Scalars['ID']>;
  name?: InputMaybe<Scalars['String']>;
  phone?: InputMaybe<PhoneInput>;
};

export type DeregisterFcmTokenResponse = MutationResponseInterface & {
  __typename?: 'DeregisterFCMTokenResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type DeviceAnalyticsDataInput = {
  /** The signup source from mobile can be android or ios */
  acquisitionSource?: InputMaybe<UserAcquisitionSourceEnum>;
  appsFlyerId?: InputMaybe<Scalars['String']>;
};

export enum DigilockerVerificationTypeEnum {
  AADHAAR_EKYC = 'AADHAAR_EKYC',
}

export type FailedPaymentsOverviewFailureResponse = {
  __typename?: 'FailedPaymentsOverviewFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type FailedPaymentsOverviewResponse =
  | FailedPaymentsOverviewFailureResponse
  | FailedPaymentsOverviewSuccessResponse;

export type FailedPaymentsOverviewSuccessResponse = {
  __typename?: 'FailedPaymentsOverviewSuccessResponse';
  bank?: Maybe<Array<Maybe<OverViewResponseType>>>;
  business?: Maybe<Array<Maybe<OverViewResponseType>>>;
  code: Scalars['PositiveInt'];
  customer?: Maybe<Array<Maybe<OverViewResponseType>>>;
  message?: Maybe<Scalars['String']>;
  others?: Maybe<Array<Maybe<OverViewResponseType>>>;
  success: Scalars['Boolean'];
};

export enum FeeBasedGatingPaymentStatusEnum {
  AUTHORIZED = 'AUTHORIZED',
  CAPTURED = 'CAPTURED',
  CREATED = 'CREATED',
  FAILED = 'FAILED',
  PENDING = 'PENDING',
  UNINITIALIZED = 'UNINITIALIZED',
}

export enum FeeBearerEnum {
  CUSTOMER = 'CUSTOMER',
  PLATFORM = 'PLATFORM',
}

export type FieldDetailsInput = {
  bank?: InputMaybe<MerchantBankInput>;
  business?: InputMaybe<MerchantBusinessInput>;
  contactPerson?: InputMaybe<MerchantContactPersonInput>;
  document?: InputMaybe<MerchantDocumentInput>;
  stakeholder?: InputMaybe<MerchantStakeholderInput>;
};

export type GoalTrackerMetaData = {
  __typename?: 'GoalTrackerMetaData';
  availableUnits?: Maybe<Scalars['Int']>;
  collectedAmount?: Maybe<Scalars['Int']>;
  displayAvailableUnits?: Maybe<Scalars['Int']>;
  displayDaysLeft?: Maybe<Scalars['Int']>;
  displaySoldUnits?: Maybe<Scalars['Int']>;
  displaySupporterCount?: Maybe<Scalars['Int']>;
  goalAmount?: Maybe<Scalars['Int']>;
  goalEndTimestamp?: Maybe<Scalars['DateTime']>;
  goalEndTimestampFormatted?: Maybe<Scalars['Int']>;
  soldUnits?: Maybe<Scalars['Int']>;
  supporterCount?: Maybe<Scalars['Int']>;
};

export type GoalTrackerSettings = {
  __typename?: 'GoalTrackerSettings';
  isActive?: Maybe<Scalars['Int']>;
  metaData?: Maybe<GoalTrackerMetaData>;
  trackerType?: Maybe<Scalars['String']>;
};

export enum GstTypeEnum {
  INTER_STATE = 'INTER_STATE',
  INTRA_STATE = 'INTRA_STATE',
}

export type Image = {
  __typename?: 'Image';
  alt?: Maybe<Scalars['String']>;
  description?: Maybe<Scalars['String']>;
  src: Scalars['URL'];
};

export type Invoice = {
  __typename?: 'Invoice';
  amount: InvoiceAmount;
  comments?: Maybe<Scalars['String']>;
  customer?: Maybe<Customer>;
  dates?: Maybe<InvoiceDate>;
  description?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  isPartiallyPayable?: Maybe<Scalars['Boolean']>;
  items: Array<InvoiceItem>;
  notes?: Maybe<Scalars['JSONObject']>;
  number?: Maybe<Scalars['String']>;
  payment?: Maybe<Payment>;
  receiptNumber?: Maybe<Scalars['String']>;
  smsStatus?: Maybe<InvoiceSmsStatusEnum>;
  status?: Maybe<InvoiceStatusEnum>;
  type?: Maybe<InvoiceTypeEnum>;
  url?: Maybe<Scalars['URL']>;
};

export type InvoiceAmount = {
  __typename?: 'InvoiceAmount';
  due?: Maybe<Money>;
  generated: Money;
  paid?: Maybe<Money>;
};

export type InvoiceDate = {
  __typename?: 'InvoiceDate';
  cancelledAt?: Maybe<Scalars['DateTime']>;
  createdAt?: Maybe<Scalars['DateTime']>;
  expireBy?: Maybe<Scalars['DateTime']>;
  expiredAt?: Maybe<Scalars['DateTime']>;
  issuedAt?: Maybe<Scalars['DateTime']>;
  paidAt?: Maybe<Scalars['DateTime']>;
};

export type InvoiceItem = {
  __typename?: 'InvoiceItem';
  amount: Money;
  description?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  name?: Maybe<Scalars['String']>;
  number?: Maybe<Scalars['String']>;
  quantity?: Maybe<Scalars['Int']>;
};

export enum InvoiceSmsStatusEnum {
  PENDING = 'PENDING',
  SENT = 'SENT',
}

export enum InvoiceStatusEnum {
  CANCELLED = 'CANCELLED',
  DELETED = 'DELETED',
  DRAFT = 'DRAFT',
  EXPIRED = 'EXPIRED',
  ISSUED = 'ISSUED',
  PAID = 'PAID',
  PARTIALLY_PAID = 'PARTIALLY_PAID',
}

export enum InvoiceTypeEnum {
  ECOD = 'ECOD',
  INVOICE = 'INVOICE',
  LINK = 'LINK',
}

export type InvoicesResponse = PaginationResponseInterface & {
  __typename?: 'InvoicesResponse';
  hasMore: Scalars['Boolean'];
  invoices: Array<Invoice>;
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  total: Scalars['NonNegativeInt'];
};

export type LoginOtpError = MutationResponseInterface & {
  __typename?: 'LoginOtpError';
  code: Scalars['PositiveInt'];
  errorCode?: Maybe<LoginOtpErrorCodeEnum>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum LoginOtpErrorCodeEnum {
  BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED = 'BAD_REQUEST_CONTACT_MOBILE_NOT_VERIFIED',
  BAD_REQUEST_EMAIL_LOGIN_OTP_SEND_THRESHOLD_EXHAUSTED = 'BAD_REQUEST_EMAIL_LOGIN_OTP_SEND_THRESHOLD_EXHAUSTED',
  BAD_REQUEST_EMAIL_NOT_VERIFIED = 'BAD_REQUEST_EMAIL_NOT_VERIFIED',
  BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED = 'BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED',
  BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED = 'BAD_REQUEST_MOBILE_OTP_LOGIN_NOT_ALLOWED',
  BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED = 'BAD_REQUEST_MULTIPLE_ACCOUNTS_ASSOCIATED',
  BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED = 'BAD_REQUEST_NO_ACCOUNTS_ASSOCIATED',
  BAD_REQUEST_NO_RECORDS_FOUND = 'BAD_REQUEST_NO_RECORDS_FOUND',
  BAD_REQUEST_OTP_LOGIN_LOCKED = 'BAD_REQUEST_OTP_LOGIN_LOCKED',
  EMPTY_RESPONSE = 'EMPTY_RESPONSE',
}

export type LoginOtpResendError = {
  __typename?: 'LoginOtpResendError';
  code: Scalars['PositiveInt'];
  errorCode: LoginOtpErrorCodeEnum;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type LoginOtpResendResponse = LoginOtpResendError | LoginOtpResendSuccess;

export type LoginOtpResendSuccess = {
  __typename?: 'LoginOtpResendSuccess';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token: Scalars['String'];
};

export type LoginOtpResponse = LoginOtpError | LoginOtpSuccess;

export type LoginOtpSuccess = MutationResponseInterface & {
  __typename?: 'LoginOtpSuccess';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token: Scalars['String'];
};

export type Merchant = {
  __typename?: 'Merchant';
  activation: MerchantActivation;
  apiKeys: Array<MerchantApiKey>;
  bank: MerchantBank;
  bankingAccounts?: Maybe<Array<MerchantBankingAccount>>;
  business: MerchantBusiness;
  configurations?: Maybe<MerchantConfiguration>;
  contactPerson: MerchantContactPerson;
  createdAt: Scalars['DateTime'];
  document: MerchantDocument;
  domesticLimit?: Maybe<Scalars['BigInt']>;
  hasApiKeyAccess: Scalars['Boolean'];
  id: Scalars['ID'];
  internationalLimit?: Maybe<Scalars['BigInt']>;
  isFundsOnHold: Scalars['Boolean'];
  isPresignupComplete?: Maybe<Scalars['Boolean']>;
  /** @deprecated property removed from backend */
  isTransactionCouponApplied?: Maybe<Scalars['Boolean']>;
  isTwoFactorEnabled: Scalars['Boolean'];
  logo?: Maybe<Scalars['URL']>;
  name?: Maybe<MerchantName>;
  /** @deprecated Use user.roles */
  role?: Maybe<MerchantRoleEnum>;
  stakeholder: MerchantStakeholder;
  users: Array<User>;
  websiteTermsAndConditions?: Maybe<MerchantWebsiteTermsAndConditions>;
};

export type MerchantAcceptanceChannel = {
  __typename?: 'MerchantAcceptanceChannel';
  accept: Scalars['Boolean'];
  complianceConsent?: Maybe<Scalars['Boolean']>;
  socialMediaUrls: Array<MerchantSocialMediaUrlField>;
  urls: Array<MerchantUrlField>;
  value?: Maybe<Scalars['String']>;
};

export type MerchantAcceptanceChannelInput = {
  accept?: InputMaybe<Scalars['Boolean']>;
  complianceConsent?: InputMaybe<Scalars['Boolean']>;
  socialMediaUrls?: InputMaybe<Array<MerchantSocialMediaUrlInputField>>;
  urls?: InputMaybe<Array<InputMaybe<MerchantUrlInputField>>>;
  value?: InputMaybe<Scalars['String']>;
};

export type MerchantAcceptanceChannelWhatsappSmsEmail = {
  __typename?: 'MerchantAcceptanceChannelWhatsappSmsEmail';
  accept: Scalars['Boolean'];
};

export type MerchantAcceptanceChannelWhatsappSmsEmailInput = {
  accept: Scalars['Boolean'];
};

export type MerchantActivation = {
  __typename?: 'MerchantActivation';
  activationStatusChangeLogs: Array<MerchantActivationStatusEnum>;
  allowedStatus: Array<Maybe<MerchantActivationStatusEnum>>;
  canSubmitForm: Scalars['Boolean'];
  dedupe?: Maybe<MerchantActivationDedupe>;
  documentsSubmittedAt?: Maybe<Scalars['DateTime']>;
  feeBasedGating?: Maybe<MerchantFeeBasedGating>;
  flow?: Maybe<MerchantActivationFlow>;
  isActivated: Scalars['Boolean'];
  isAutoKycDone: Scalars['Boolean'];
  isDedupe: Scalars['Boolean'];
  isFormLocked?: Maybe<Scalars['Boolean']>;
  isFormSubmitted: Scalars['Boolean'];
  isHardLimitReached: Scalars['Boolean'];
  isInternational: Scalars['Boolean'];
  /** Signifies whether the user has transacted or not */
  isTransacted: Scalars['Boolean'];
  merchantEscalations: MerchantEscalations;
  milestone?: Maybe<MerchantActivationMilestoneEnum>;
  paymentsActivatedAt?: Maybe<Scalars['DateTime']>;
  progressPercent: Scalars['NonNegativeInt'];
  status?: Maybe<MerchantActivationStatusEnum>;
};

export type MerchantActivationDedupe = {
  __typename?: 'MerchantActivationDedupe';
  isMatch: Scalars['Boolean'];
  isUnderReview: Scalars['Boolean'];
};

export type MerchantActivationEscalationsBreached = {
  __typename?: 'MerchantActivationEscalationsBreached';
  amount: Money;
  currentEscalationLimit: MerchantEscalationLimit;
  escalationAction?: Maybe<MerchantEscalationAction>;
  escalationType: MerchantEscalationTypeEnum;
  id: Scalars['ID'];
  nextEscalationLimit?: Maybe<MerchantEscalationLimit>;
  transactionLimit: MerchantTransactionLimit;
};

export type MerchantActivationEscalationsNotBreached = {
  __typename?: 'MerchantActivationEscalationsNotBreached';
  transactionLimit: MerchantTransactionLimit;
};

export type MerchantActivationFlow = {
  __typename?: 'MerchantActivationFlow';
  domestic?: Maybe<MerchantActivationFlowEnum>;
  international?: Maybe<MerchantActivationFlowEnum>;
};

export enum MerchantActivationFlowEnum {
  BLACKLIST = 'BLACKLIST',
  GREYLIST = 'GREYLIST',
  WHITELIST = 'WHITELIST',
}

export enum MerchantActivationMilestoneEnum {
  ACTIVATION_FLOW_COMPLETED = 'ACTIVATION_FLOW_COMPLETED',
  FUNDS_ON_HOLD_REMINDER = 'FUNDS_ON_HOLD_REMINDER',
  HARD_LIMIT_LEVEL_1 = 'HARD_LIMIT_LEVEL_1',
  HARD_LIMIT_LEVEL_2 = 'HARD_LIMIT_LEVEL_2',
  HARD_LIMIT_LEVEL_4 = 'HARD_LIMIT_LEVEL_4',
  L1_COMPLETED = 'L1_COMPLETED',
  L2_COMPLETED = 'L2_COMPLETED',
  P2PM = 'P2PM',
  SOFT_LIMIT = 'SOFT_LIMIT',
  SOFT_LIMIT_LEVEL_1 = 'SOFT_LIMIT_LEVEL_1',
}

export type MerchantActivationResponse = MutationResponseInterface & {
  __typename?: 'MerchantActivationResponse';
  code: Scalars['PositiveInt'];
  merchant: Merchant;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum MerchantActivationStatusEnum {
  ACTIVATED = 'ACTIVATED',
  ACTIVATED_KYC_PENDING = 'ACTIVATED_KYC_PENDING',
  ACTIVATED_MCC_PENDING = 'ACTIVATED_MCC_PENDING',
  INSTANTLY_ACTIVATED = 'INSTANTLY_ACTIVATED',
  KYC_QUALIFIED_UNACTIVATED = 'KYC_QUALIFIED_UNACTIVATED',
  NEEDS_CLARIFICATION = 'NEEDS_CLARIFICATION',
  P2PM_ACTIVATED = 'P2PM_ACTIVATED',
  REJECTED = 'REJECTED',
  UNDER_REVIEW = 'UNDER_REVIEW',
}

export type MerchantAddress = {
  __typename?: 'MerchantAddress';
  city: MerchantStringField;
  country: MerchantStringField;
  district: MerchantStringField;
  line1: MerchantStringField;
  line2: MerchantStringField;
  state: MerchantStringField;
  zipCode: MerchantStringField;
};

export type MerchantAddressInput = {
  city?: InputMaybe<MerchantStringInputField>;
  country?: InputMaybe<MerchantStringInputField>;
  district?: InputMaybe<MerchantStringInputField>;
  line1?: InputMaybe<MerchantStringInputField>;
  line2?: InputMaybe<MerchantStringInputField>;
  state?: InputMaybe<MerchantStringInputField>;
  zipCode?: InputMaybe<MerchantStringInputField>;
};

export type MerchantAnalytics = {
  __typename?: 'MerchantAnalytics';
  data?: Maybe<Scalars['JSONObject']>;
};

export type MerchantApiKey = MerchantApiKeyInterface & {
  __typename?: 'MerchantApiKey';
  createdAt: Scalars['DateTime'];
  expiredAt?: Maybe<Scalars['DateTime']>;
  id: Scalars['String'];
  updatedAt: Scalars['DateTime'];
};

export type MerchantApiKeyCreateResponse = MerchantApiKeyInterface &
  MutationResponseInterface & {
    __typename?: 'MerchantApiKeyCreateResponse';
    code: Scalars['PositiveInt'];
    createdAt: Scalars['DateTime'];
    expiredAt?: Maybe<Scalars['DateTime']>;
    id: Scalars['String'];
    message?: Maybe<Scalars['String']>;
    secret: Scalars['String'];
    success: Scalars['Boolean'];
    updatedAt: Scalars['DateTime'];
  };

export type MerchantApiKeyInterface = {
  createdAt: Scalars['DateTime'];
  expiredAt?: Maybe<Scalars['DateTime']>;
  id: Scalars['String'];
  updatedAt: Scalars['DateTime'];
};

export type MerchantApiKeyRegenerateNew = MerchantApiKeyInterface & {
  __typename?: 'MerchantApiKeyRegenerateNew';
  createdAt: Scalars['DateTime'];
  expiredAt?: Maybe<Scalars['DateTime']>;
  id: Scalars['String'];
  secret: Scalars['String'];
  updatedAt: Scalars['DateTime'];
};

export type MerchantApiKeyRegenerateOld = MerchantApiKeyInterface & {
  __typename?: 'MerchantApiKeyRegenerateOld';
  createdAt: Scalars['DateTime'];
  expiredAt?: Maybe<Scalars['DateTime']>;
  id: Scalars['String'];
  updatedAt: Scalars['DateTime'];
};

export type MerchantApiKeyRegenerateResponse = MutationResponseInterface & {
  __typename?: 'MerchantApiKeyRegenerateResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  newApiKey: MerchantApiKeyRegenerateNew;
  oldApiKey: MerchantApiKeyRegenerateOld;
  success: Scalars['Boolean'];
};

export type MerchantApiKeysCreateFailure = MutationResponseInterface & {
  __typename?: 'MerchantApiKeysCreateFailure';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantApiKeysCreateResponse =
  | MerchantApiKeysCreateFailure
  | MerchantApiKeysCreateSuccess;

export type MerchantApiKeysCreateSuccess = MerchantApiKeyInterface &
  MutationResponseInterface & {
    __typename?: 'MerchantApiKeysCreateSuccess';
    code: Scalars['PositiveInt'];
    createdAt: Scalars['DateTime'];
    expiredAt?: Maybe<Scalars['DateTime']>;
    id: Scalars['String'];
    message?: Maybe<Scalars['String']>;
    secret: Scalars['String'];
    success: Scalars['Boolean'];
    updatedAt: Scalars['DateTime'];
  };

export type MerchantAverageOrderField = MerchantFieldInterface & {
  __typename?: 'MerchantAverageOrderField';
  clarificationReasons: Array<MerchantFieldClarificationReason>;
  value?: Maybe<MerchantAverageOrderFieldValue>;
  verificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantAverageOrderFieldValue = {
  __typename?: 'MerchantAverageOrderFieldValue';
  max: Scalars['Int'];
  min: Scalars['Int'];
};

export type MerchantBalance = {
  __typename?: 'MerchantBalance';
  accountType?: Maybe<MerchantBalanceAccountTypeEnum>;
  amount: Money;
  id: Scalars['ID'];
  productType: MerchantBalanceProductTypeEnum;
  virtualAccountsResponse?: Maybe<MerchantVirtualAccountsResponse>;
};

export type MerchantBalanceVirtualAccountsResponseArgs = {
  virtualAccountStatus?: InputMaybe<MerchantVirtualAccountStatus>;
  virtualAccountsLimit: Scalars['PositiveInt'];
  virtualAccountsOffset: Scalars['NonNegativeInt'];
};

export enum MerchantBalanceAccountTypeEnum {
  CURRENT_ACCOUNT_DIRECT = 'CURRENT_ACCOUNT_DIRECT',
  CURRENT_ACCOUNT_ON_NODAL = 'CURRENT_ACCOUNT_ON_NODAL',
}

export enum MerchantBalanceProductTypeEnum {
  BANKING = 'BANKING',
  CHARGE = 'CHARGE',
  INTEREST = 'INTEREST',
  PRIMARY = 'PRIMARY',
  PRINCIPAL = 'PRINCIPAL',
  RESERVE_PRIMARY = 'RESERVE_PRIMARY',
}

export type MerchantBank = {
  __typename?: 'MerchantBank';
  accountName: MerchantStringField;
  accountNumber: MerchantStringField;
  /** fuzzyScore is used to match the Bank Account details with the given PAN Details */
  fuzzyScore?: Maybe<Scalars['Int']>;
  ifsc: MerchantStringField;
  verificationErrorCode?: Maybe<MerchantBankVerificationErrorCodeEnum>;
  verificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantBankAccountDetails = {
  __typename?: 'MerchantBankAccountDetails';
  accountNumber: Scalars['String'];
  beneficiaryName: Scalars['String'];
  ifscCode: Scalars['String'];
};

export type MerchantBankAccountDocumentUploadFailureResponse = {
  __typename?: 'MerchantBankAccountDocumentUploadFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantBankAccountDocumentUploadResponse =
  | MerchantBankAccountDocumentUploadFailureResponse
  | MerchantBankAccountDocumentUploadSuccessResponse;

export type MerchantBankAccountDocumentUploadSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantBankAccountDocumentUploadSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantBankAccountUpdateFailureResponse = MutationResponseInterface & {
  __typename?: 'MerchantBankAccountUpdateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantBankAccountUpdateResponse =
  | MerchantBankAccountUpdateFailureResponse
  | MerchantBankAccountUpdateSuccessResponse;

export type MerchantBankAccountUpdateSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantBankAccountUpdateSuccessResponse';
  bankAccountDetails?: Maybe<MerchantBankAccountDetails>;
  code: Scalars['PositiveInt'];
  isSyncFlow: Scalars['Boolean'];
  isTimeOut: Scalars['Boolean'];
  isWorkFlowCreated: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantBankDetails = {
  __typename?: 'MerchantBankDetails';
  accountName?: Maybe<Scalars['String']>;
  accountNumber?: Maybe<Scalars['String']>;
  bankAccountId?: Maybe<Scalars['ID']>;
  bankName?: Maybe<Scalars['String']>;
  ifsc?: Maybe<Scalars['String']>;
  updatedAt?: Maybe<Scalars['DateTime']>;
};

export type MerchantBankDetailsFailureResponse = {
  __typename?: 'MerchantBankDetailsFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantBankDetailsResponse =
  | MerchantBankDetailsFailureResponse
  | MerchantBankDetailsSuccessResponse;

export type MerchantBankDetailsSuccessResponse = {
  __typename?: 'MerchantBankDetailsSuccessResponse';
  bank: MerchantBankDetails;
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantBankInput = {
  accountName?: InputMaybe<MerchantStringInputField>;
  accountNumber?: InputMaybe<MerchantStringInputField>;
  bankProof?: InputMaybe<MerchantStringInputField>;
  ifsc?: InputMaybe<MerchantStringInputField>;
};

export enum MerchantBankVerificationErrorCodeEnum {
  ACCOUNT_BLOCKED_OR_FROZEN = 'ACCOUNT_BLOCKED_OR_FROZEN',
  ACCOUNT_CLOSED = 'ACCOUNT_CLOSED',
  INVALID_ACCOUNT = 'INVALID_ACCOUNT',
  INVALID_BENEFICIARY_IFSC_CODE_OR_NBIN = 'INVALID_BENEFICIARY_IFSC_CODE_OR_NBIN',
  INVALID_BENEFICIARY_NUMBER_OR_IFSC = 'INVALID_BENEFICIARY_NUMBER_OR_IFSC',
  NOT_MATCHED = 'NOT_MATCHED',
  NRE_ACCOUNT = 'NRE_ACCOUNT',
}

export type MerchantBankingAccount = {
  __typename?: 'MerchantBankingAccount';
  balance?: Maybe<MerchantBankingAccountBalance>;
  currency: Currency;
  id: Scalars['ID'];
  ifsc?: Maybe<Scalars['String']>;
  number?: Maybe<Scalars['String']>;
  partnerBankName: Scalars['String'];
  pincode?: Maybe<Scalars['PositiveInt']>;
  status: MerchantBankingAccountStatusEnum;
  type: MerchantBankingAccountTypeEnum;
};

export type MerchantBankingAccountBalance = {
  __typename?: 'MerchantBankingAccountBalance';
  amount: Money;
  id: Scalars['ID'];
  lastCheckedAt?: Maybe<Scalars['DateTime']>;
};

export enum MerchantBankingAccountStatusEnum {
  ACCOUNT_ACTIVATION = 'ACCOUNT_ACTIVATION',
  ACCOUNT_OPENING = 'ACCOUNT_OPENING',
  ACTIVATED = 'ACTIVATED',
  API_ONBOARDING = 'API_ONBOARDING',
  ARCHIVED = 'ARCHIVED',
  CANCELLED = 'CANCELLED',
  CREATED = 'CREATED',
  DOC_COLLECTION = 'DOC_COLLECTION',
  INITIATED = 'INITIATED',
  PICKED = 'PICKED',
  PROCESSED = 'PROCESSED',
  PROCESSING = 'PROCESSING',
  REJECTED = 'REJECTED',
  UNSERVICEABLE = 'UNSERVICEABLE',
  VERIFICATION_CALL = 'VERIFICATION_CALL',
}

export enum MerchantBankingAccountTypeEnum {
  CURRENT_ACCOUNT_DIRECT = 'CURRENT_ACCOUNT_DIRECT',
  CURRENT_ACCOUNT_ON_NODAL = 'CURRENT_ACCOUNT_ON_NODAL',
}

export type MerchantBankingAccountsBalanceResponse = {
  __typename?: 'MerchantBankingAccountsBalanceResponse';
  balances: Array<MerchantBankingAccountBalance>;
};

export type MerchantBankingRole = {
  __typename?: 'MerchantBankingRole';
  id?: Maybe<Scalars['String']>;
  name?: Maybe<Scalars['String']>;
};

export type MerchantBusiness = {
  __typename?: 'MerchantBusiness';
  address: MerchantBusinessAddress;
  averageOrder: MerchantAverageOrderField;
  billingLabel: MerchantStringField;
  blacklistedConsentCategory?: Maybe<Scalars['String']>;
  businessPan: MerchantStringField;
  category: MerchantStringField;
  companyCin: MerchantStringField;
  gstin: MerchantStringField;
  model: MerchantStringField;
  name: MerchantStringField;
  parentCategory: MerchantStringField;
  paymentAcceptanceChannels: MerchantPaymentAcceptanceChannels;
  shopEstablishment: MerchantShopEstablishment;
  subCategory: MerchantStringField;
  type: MerchantBusinessTypeField;
  /** @deprecated Use paymentAcceptanceChannels.websites.urls[0].value */
  websites: Array<MerchantUrlField>;
};

export type MerchantBusinessAddress = {
  __typename?: 'MerchantBusinessAddress';
  operation: MerchantAddress;
  registered: MerchantAddress;
};

export type MerchantBusinessAddressInput = {
  operation?: InputMaybe<MerchantAddressInput>;
  registered?: InputMaybe<MerchantAddressInput>;
};

export type MerchantBusinessAppDetailsResponse = MutationResponseInterface & {
  __typename?: 'MerchantBusinessAppDetailsResponse';
  code: Scalars['PositiveInt'];
  message: Scalars['String'];
  success: Scalars['Boolean'];
};

export type MerchantBusinessAppInput = {
  businessAppPassword?: InputMaybe<Scalars['String']>;
  businessAppUrl: Scalars['URL'];
  businessAppUsername?: InputMaybe<Scalars['String']>;
};

export type MerchantBusinessCategoriesResponse = {
  __typename?: 'MerchantBusinessCategoriesResponse';
  categoryName: Scalars['String'];
  categoryValue: Scalars['String'];
  subCategories: Array<MerchantBusinessSubCategory>;
};

export type MerchantBusinessInput = {
  address?: InputMaybe<MerchantBusinessAddressInput>;
  billingLabel?: InputMaybe<MerchantStringInputField>;
  blacklistedConsentCategory?: InputMaybe<Scalars['String']>;
  businessPan?: InputMaybe<MerchantStringInputField>;
  category?: InputMaybe<MerchantStringInputField>;
  companyCin?: InputMaybe<MerchantStringInputField>;
  gstin?: InputMaybe<MerchantStringInputField>;
  model?: InputMaybe<MerchantStringInputField>;
  name?: InputMaybe<MerchantStringInputField>;
  parentCategory?: InputMaybe<MerchantStringInputField>;
  paymentAcceptanceChannels?: InputMaybe<MerchantPaymentAcceptanceChannelsInput>;
  shopEstablishment?: InputMaybe<MerchantShopEstablishmentInput>;
  subCategory?: InputMaybe<MerchantStringInputField>;
  type?: InputMaybe<MerchantBusinessTypeInputField>;
  websites?: InputMaybe<Array<InputMaybe<MerchantUrlInputField>>>;
};

export type MerchantBusinessParentCategory = {
  __typename?: 'MerchantBusinessParentCategory';
  categories: Array<MerchantBusinessCategoriesResponse>;
  parentCategoryName: Scalars['String'];
  parentCategoryValue: Scalars['String'];
};

export type MerchantBusinessSubCategory = {
  __typename?: 'MerchantBusinessSubCategory';
  activationFlow: MerchantActivationFlowEnum;
  name: Scalars['String'];
  nonRegisteredActivationFlow: MerchantActivationFlowEnum;
  tags: Array<Maybe<Scalars['String']>>;
  value: Scalars['String'];
};

export enum MerchantBusinessTypeEnum {
  EDUCATIONAL_INSTITUTES = 'EDUCATIONAL_INSTITUTES',
  HUF = 'HUF',
  LLP = 'LLP',
  NGO = 'NGO',
  OTHERS = 'OTHERS',
  PARTNERSHIP = 'PARTNERSHIP',
  PRIVATE = 'PRIVATE',
  PROPRIETORSHIP = 'PROPRIETORSHIP',
  PUBLIC = 'PUBLIC',
  SOCIETY = 'SOCIETY',
  TRUST = 'TRUST',
  UNREGISTERED = 'UNREGISTERED',
  /** UNREGISTERED_OLD is used for old unregistred merchants with value 2 */
  UNREGISTERED_OLD = 'UNREGISTERED_OLD',
}

export type MerchantBusinessTypeField = MerchantFieldInterface & {
  __typename?: 'MerchantBusinessTypeField';
  clarificationReasons: Array<MerchantFieldClarificationReason>;
  value?: Maybe<MerchantBusinessTypeEnum>;
  verificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantBusinessTypeInputField = {
  clarificationReasons?: InputMaybe<Array<MerchantClarificationInputType>>;
  value?: InputMaybe<MerchantBusinessTypeEnum>;
};

export type MerchantBusinessTypesResponse = {
  __typename?: 'MerchantBusinessTypesResponse';
  registered: Array<BusinessType>;
  unregistered: Array<BusinessType>;
};

export type MerchantBusinessWebsiteDetailsResponse = MutationResponseInterface & {
  __typename?: 'MerchantBusinessWebsiteDetailsResponse';
  code: Scalars['PositiveInt'];
  message: Scalars['String'];
  success: Scalars['Boolean'];
};

export type MerchantBusinessWebsiteInput = {
  businessWebsiteMainPage: Scalars['URL'];
};

export type MerchantClarificationDetail = {
  __typename?: 'MerchantClarificationDetail';
  aadharBack?: Maybe<MerchantClarifications>;
  aadharFront?: Maybe<MerchantClarifications>;
  address?: Maybe<MerchantClarifications>;
  affiliationCertificate?: Maybe<MerchantClarifications>;
  amfiCertificate?: Maybe<MerchantClarifications>;
  appstoreUrl?: Maybe<MerchantClarifications>;
  ayushCertificate?: Maybe<MerchantClarifications>;
  bankDetails?: Maybe<MerchantClarifications>;
  bankStatement?: Maybe<MerchantClarifications>;
  bankVerificationLetter?: Maybe<MerchantClarifications>;
  barCouncilCertificate?: Maybe<MerchantClarifications>;
  bbpsDocument?: Maybe<MerchantClarifications>;
  bisCertificate?: Maybe<MerchantClarifications>;
  boardResolutionLetter?: Maybe<MerchantClarifications>;
  brandTieUpDocument?: Maybe<MerchantClarifications>;
  businessCategory?: Maybe<MerchantClarifications>;
  businessCorrespondentDocument?: Maybe<MerchantClarifications>;
  businessDba?: Maybe<MerchantClarifications>;
  businessDescription?: Maybe<MerchantClarifications>;
  businessPan?: Maybe<MerchantClarifications>;
  businessPanName?: Maybe<MerchantClarifications>;
  businessPanNumber?: Maybe<MerchantClarifications>;
  businessRegistration?: Maybe<MerchantClarifications>;
  businessType?: Maybe<MerchantClarifications>;
  businessWebsite?: Maybe<MerchantClarifications>;
  cancelledCheque?: Maybe<MerchantClarifications>;
  cancelledChequeVideo?: Maybe<MerchantClarifications>;
  cinDetails?: Maybe<MerchantClarifications>;
  contactEmail?: Maybe<MerchantClarifications>;
  contactMobile?: Maybe<MerchantClarifications>;
  contactName?: Maybe<MerchantClarifications>;
  copywriteLicense?: Maybe<MerchantClarifications>;
  cpvReport?: Maybe<MerchantClarifications>;
  dealershipRightsCertificate?: Maybe<MerchantClarifications>;
  dgcaCertificate?: Maybe<MerchantClarifications>;
  domainOwnershipDocument?: Maybe<MerchantClarifications>;
  dotCertificate?: Maybe<MerchantClarifications>;
  driverLicenseBack?: Maybe<MerchantClarifications>;
  driverLicenseFront?: Maybe<MerchantClarifications>;
  epfSchemeCertificate?: Maybe<MerchantClarifications>;
  fdaCertificate?: Maybe<MerchantClarifications>;
  fdaLicense?: Maybe<MerchantClarifications>;
  ffmcLicense?: Maybe<MerchantClarifications>;
  form8a?: Maybe<MerchantClarifications>;
  form10ac?: Maybe<MerchantClarifications>;
  form12a?: Maybe<MerchantClarifications>;
  form80g?: Maybe<MerchantClarifications>;
  form2020b2121b?: Maybe<MerchantClarifications>;
  fssaiCertificate?: Maybe<MerchantClarifications>;
  fssaiLicense?: Maybe<MerchantClarifications>;
  giaCertificate?: Maybe<MerchantClarifications>;
  giiCertificate?: Maybe<MerchantClarifications>;
  govtAuthorisationLetter?: Maybe<MerchantClarifications>;
  gstCertificate?: Maybe<MerchantClarifications>;
  gstDetails?: Maybe<MerchantClarifications>;
  iataCertificate?: Maybe<MerchantClarifications>;
  iatoLicense?: Maybe<MerchantClarifications>;
  iecLicense?: Maybe<MerchantClarifications>;
  invoice?: Maybe<MerchantClarifications>;
  irctcAgentAgreement?: Maybe<MerchantClarifications>;
  irdaCertificate?: Maybe<MerchantClarifications>;
  irdaiRegistrationCertificate?: Maybe<MerchantClarifications>;
  legalOpinionDocument?: Maybe<MerchantClarifications>;
  liquorLicense?: Maybe<MerchantClarifications>;
  manufacturingLicense?: Maybe<MerchantClarifications>;
  merchantServiceAgreement?: Maybe<MerchantClarifications>;
  mmtcPampLicense?: Maybe<MerchantClarifications>;
  msmeCertificate?: Maybe<MerchantClarifications>;
  msoDocument?: Maybe<MerchantClarifications>;
  nationalHousingBankCertificate?: Maybe<MerchantClarifications>;
  nbfcRegistrationCertificate?: Maybe<MerchantClarifications>;
  ncCount?: Maybe<Scalars['Int']>;
  operationAddress?: Maybe<MerchantClarifications>;
  passportBack?: Maybe<MerchantClarifications>;
  passportFront?: Maybe<MerchantClarifications>;
  pciDssCertificate?: Maybe<MerchantClarifications>;
  personalPan?: Maybe<MerchantClarifications>;
  /** @deprecated Use pesoLicense instead */
  pescoLicense?: Maybe<MerchantClarifications>;
  pesoLicense?: Maybe<MerchantClarifications>;
  pharmacyDrugLicense?: Maybe<MerchantClarifications>;
  playstoreUrl?: Maybe<MerchantClarifications>;
  pmWaniCertificate?: Maybe<MerchantClarifications>;
  ppiLicense?: Maybe<MerchantClarifications>;
  promoterPanDetails?: Maybe<MerchantClarifications>;
  proofOfProfession?: Maybe<MerchantClarifications>;
  rbiCertificate?: Maybe<MerchantClarifications>;
  registeredAddress?: Maybe<MerchantClarifications>;
  reraLicense?: Maybe<MerchantClarifications>;
  resellerAgreement?: Maybe<MerchantClarifications>;
  safegoldPartnershipDocument?: Maybe<MerchantClarifications>;
  sebiRegistrationCertificate?: Maybe<MerchantClarifications>;
  shopEstablishmentCertificate?: Maybe<MerchantClarifications>;
  shopEstablishmentNumber?: Maybe<MerchantClarifications>;
  slaAmfiCertificate?: Maybe<MerchantClarifications>;
  slaDealershipAgreement?: Maybe<MerchantClarifications>;
  slaDocument?: Maybe<MerchantClarifications>;
  slaFfmcLicense?: Maybe<MerchantClarifications>;
  slaIataCertificate?: Maybe<MerchantClarifications>;
  slaIrdaiRegistrationCertificate?: Maybe<MerchantClarifications>;
  slaNbfcRegistrationCertificate?: Maybe<MerchantClarifications>;
  slaSebiRegistrationCertificate?: Maybe<MerchantClarifications>;
  tradeLicense?: Maybe<MerchantClarifications>;
  traiCertificate?: Maybe<MerchantClarifications>;
  undertaking?: Maybe<MerchantClarifications>;
  undertakingDocument?: Maybe<MerchantClarifications>;
  voterIdBack?: Maybe<MerchantClarifications>;
  voterIdFront?: Maybe<MerchantClarifications>;
};

export type MerchantClarificationDetailsResponse = {
  __typename?: 'MerchantClarificationDetailsResponse';
  clarificationDetails: MerchantClarificationDetail;
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantClarificationDetailsSubmitResponse = MutationResponseInterface & {
  __typename?: 'MerchantClarificationDetailsSubmitResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantClarificationDetailsUpdateResponse = MutationResponseInterface & {
  __typename?: 'MerchantClarificationDetailsUpdateResponse';
  clarificationDetails: MerchantClarificationDetail;
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantClarificationFieldValues = {
  __typename?: 'MerchantClarificationFieldValues';
  aadharBack?: Maybe<Scalars['String']>;
  aadharFront?: Maybe<Scalars['String']>;
  affiliationCertificate?: Maybe<Scalars['String']>;
  amfiCertificate?: Maybe<Scalars['String']>;
  appstoreUrl?: Maybe<Scalars['String']>;
  ayushCertificate?: Maybe<Scalars['String']>;
  bankAccountName?: Maybe<Scalars['String']>;
  bankAccountNumber?: Maybe<Scalars['String']>;
  bankBranchIfsc?: Maybe<Scalars['String']>;
  bankStatement?: Maybe<Scalars['String']>;
  bankVerificationLetter?: Maybe<Scalars['String']>;
  barCouncilCertificate?: Maybe<Scalars['String']>;
  bbpsDocument?: Maybe<Scalars['String']>;
  bisCertificate?: Maybe<Scalars['String']>;
  boardResolutionLetter?: Maybe<Scalars['String']>;
  brandTieUpDocument?: Maybe<Scalars['String']>;
  businessCorrespondentDocument?: Maybe<Scalars['String']>;
  businessDba?: Maybe<Scalars['String']>;
  businessDescription?: Maybe<Scalars['String']>;
  businessModel?: Maybe<Scalars['String']>;
  businessName?: Maybe<Scalars['String']>;
  businessOperationAddress?: Maybe<Scalars['String']>;
  businessOperationCity?: Maybe<Scalars['String']>;
  businessOperationPin?: Maybe<Scalars['String']>;
  businessOperationState?: Maybe<Scalars['String']>;
  businessPan?: Maybe<Scalars['String']>;
  businessRegisteredAddress?: Maybe<Scalars['String']>;
  businessRegisteredCity?: Maybe<Scalars['String']>;
  businessRegisteredPin?: Maybe<Scalars['String']>;
  businessRegisteredState?: Maybe<Scalars['String']>;
  businessRegistration?: Maybe<Scalars['String']>;
  businessType?: Maybe<Scalars['String']>;
  businessWebsite?: Maybe<Scalars['String']>;
  cancelledCheque?: Maybe<Scalars['String']>;
  cancelledChequeVideo?: Maybe<Scalars['String']>;
  companyCin?: Maybe<Scalars['String']>;
  companyPan?: Maybe<Scalars['String']>;
  contactEmail?: Maybe<Scalars['String']>;
  contactMobile?: Maybe<Scalars['String']>;
  contactName?: Maybe<Scalars['String']>;
  copywriteLicense?: Maybe<Scalars['String']>;
  cpvReport?: Maybe<Scalars['String']>;
  dealershipRightsCertificate?: Maybe<Scalars['String']>;
  dgcaCertificate?: Maybe<Scalars['String']>;
  domainOwnershipDocument?: Maybe<Scalars['String']>;
  dotCertificate?: Maybe<Scalars['String']>;
  driverLicenseBack?: Maybe<Scalars['String']>;
  driverLicenseFront?: Maybe<Scalars['String']>;
  epfSchemeCertificate?: Maybe<Scalars['String']>;
  fdaCertificate?: Maybe<Scalars['String']>;
  fdaLicense?: Maybe<Scalars['String']>;
  ffmcLicense?: Maybe<Scalars['String']>;
  form8a?: Maybe<Scalars['String']>;
  form10ac?: Maybe<Scalars['String']>;
  form12a?: Maybe<Scalars['String']>;
  form80g?: Maybe<Scalars['String']>;
  form2020b2121b?: Maybe<Scalars['String']>;
  fssaiCertificate?: Maybe<Scalars['String']>;
  fssaiLicense?: Maybe<Scalars['String']>;
  giaCertificate?: Maybe<Scalars['String']>;
  giiCertificate?: Maybe<Scalars['String']>;
  govtAuthorisationLetter?: Maybe<Scalars['String']>;
  gstCertificate?: Maybe<Scalars['String']>;
  gstin?: Maybe<Scalars['String']>;
  iataCertificate?: Maybe<Scalars['String']>;
  iatoLicense?: Maybe<Scalars['String']>;
  iecLicense?: Maybe<Scalars['String']>;
  invoice?: Maybe<Scalars['String']>;
  irctcAgentAgreement?: Maybe<Scalars['String']>;
  irdaCertificate?: Maybe<Scalars['String']>;
  irdaiRegistrationCertificate?: Maybe<Scalars['String']>;
  legalOpinionDocument?: Maybe<Scalars['String']>;
  liquorLicense?: Maybe<Scalars['String']>;
  manufacturingLicense?: Maybe<Scalars['String']>;
  merchantServiceAgreement?: Maybe<Scalars['String']>;
  mmtcPampLicense?: Maybe<Scalars['String']>;
  msmeCertificate?: Maybe<Scalars['String']>;
  msoDocument?: Maybe<Scalars['String']>;
  nationalHousingBankCertificate?: Maybe<Scalars['String']>;
  nbfcRegistrationCertificate?: Maybe<Scalars['String']>;
  passportBack?: Maybe<Scalars['String']>;
  passportFront?: Maybe<Scalars['String']>;
  pciDssCertificate?: Maybe<Scalars['String']>;
  personalPan?: Maybe<Scalars['String']>;
  /** @deprecated Use pesoLicense instead */
  pescoLicense?: Maybe<Scalars['String']>;
  pesoLicense?: Maybe<Scalars['String']>;
  pharmacyDrugLicense?: Maybe<Scalars['String']>;
  playstoreUrl?: Maybe<Scalars['String']>;
  pmWaniCertificate?: Maybe<Scalars['String']>;
  ppiLicense?: Maybe<Scalars['String']>;
  promoterPan?: Maybe<Scalars['String']>;
  promoterPanName?: Maybe<Scalars['String']>;
  proofOfProfession?: Maybe<Scalars['String']>;
  rbiCertificate?: Maybe<Scalars['String']>;
  reraLicense?: Maybe<Scalars['String']>;
  resellerAgreement?: Maybe<Scalars['String']>;
  safegoldPartnershipDocument?: Maybe<Scalars['String']>;
  sebiRegistrationCertificate?: Maybe<Scalars['String']>;
  shopEstablishmentCertificate?: Maybe<Scalars['String']>;
  shopEstablishmentNumber?: Maybe<Scalars['String']>;
  slaAmfiCertificate?: Maybe<Scalars['String']>;
  slaDealershipAgreement?: Maybe<Scalars['String']>;
  slaDocument?: Maybe<Scalars['String']>;
  slaFfmcLicense?: Maybe<Scalars['String']>;
  slaIataCertificate?: Maybe<Scalars['String']>;
  slaIrdaiRegistrationCertificate?: Maybe<Scalars['String']>;
  slaNbfcRegistrationCertificate?: Maybe<Scalars['String']>;
  slaSebiRegistrationCertificate?: Maybe<Scalars['String']>;
  tradeLicense?: Maybe<Scalars['String']>;
  traiCertificate?: Maybe<Scalars['String']>;
  undertaking?: Maybe<Scalars['String']>;
  undertakingDocument?: Maybe<Scalars['String']>;
  voterIdBack?: Maybe<Scalars['String']>;
  voterIdFront?: Maybe<Scalars['String']>;
};

export enum MerchantClarificationFromEnum {
  ADMIN = 'ADMIN',
  MERCHANT = 'MERCHANT',
  SYSTEM = 'SYSTEM',
}

export type MerchantClarificationInputType = {
  reasonCode: Scalars['String'];
  reasonType: MerchantClarificationTypeEnum;
};

export enum MerchantClarificationStatusEnum {
  NEEDS_CLARIFICATION = 'NEEDS_CLARIFICATION',
  SUBMITTED = 'SUBMITTED',
  UNDER_REVIEW = 'UNDER_REVIEW',
}

export enum MerchantClarificationTypeEnum {
  CUSTOM = 'CUSTOM',
  NOTE = 'NOTE',
  PREDEFINED = 'PREDEFINED',
}

export type MerchantClarifications = {
  __typename?: 'MerchantClarifications';
  comments: Array<ClarificationComments>;
  fieldValues: MerchantClarificationFieldValues;
  fields: Array<Scalars['String']>;
  ncCount?: Maybe<Scalars['Int']>;
  status: MerchantClarificationStatusEnum;
};

export enum MerchantCommentTypeEnum {
  CUSTOM = 'CUSTOM',
  NOTE = 'NOTE',
  PREDEFINED = 'PREDEFINED',
}

export type MerchantConfig = MerchantConfigFailure | MerchantOnboardingConfig;

export type MerchantConfigFailure = {
  __typename?: 'MerchantConfigFailure';
  message: Scalars['String'];
  success: Scalars['Boolean'];
};

export type MerchantConfigUpdateResponse = MutationResponseInterface & {
  __typename?: 'MerchantConfigUpdateResponse';
  code: Scalars['PositiveInt'];
  message: Scalars['String'];
  success: Scalars['Boolean'];
};

export type MerchantConfiguration = {
  __typename?: 'MerchantConfiguration';
  defaultRefundSpeed?: Maybe<PaymentRefundSpeedRequestedEnum>;
  transactionReportEmail?: Maybe<Array<Maybe<Scalars['String']>>>;
};

export enum MerchantConfigurationNamespaceEnum {
  ONBOARDING = 'ONBOARDING',
}

export type MerchantConsentData = {
  type?: InputMaybe<Scalars['String']>;
  url?: InputMaybe<Scalars['URL']>;
};

export enum MerchantConsentEvent {
  DIGILOCKER = 'DIGILOCKER',
  L2 = 'L2',
  NO_CODE_POLICY_WIZARD = 'NO_CODE_POLICY_WIZARD',
  WEBSITE_POLICY_WIZARD = 'WEBSITE_POLICY_WIZARD',
}

export type MerchantConsentFailure = MutationResponseInterface & {
  __typename?: 'MerchantConsentFailure';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantConsentInput = {
  consent?: InputMaybe<Scalars['Boolean']>;
  consentData?: InputMaybe<Array<InputMaybe<MerchantConsentData>>>;
};

export type MerchantConsentPayload = {
  isProvided: Scalars['Boolean'];
  type: MerchantConsentsTypeEnum;
  url?: InputMaybe<Scalars['URL']>;
};

export type MerchantConsentResponse = MerchantConsentFailure | MerchantConsentSuccess;

export type MerchantConsentSuccess = {
  __typename?: 'MerchantConsentSuccess';
  partnerAccess: Scalars['Boolean'];
};

export enum MerchantConsentsErrorTypeEnum {
  BAD_REQUEST_ERROR = 'BAD_REQUEST_ERROR',
  EMPTY_RESPONSE = 'EMPTY_RESPONSE',
  SERVER_ERROR = 'SERVER_ERROR',
}

export type MerchantConsentsFailureResponse = {
  __typename?: 'MerchantConsentsFailureResponse';
  code: Scalars['PositiveInt'];
  errorCode: MerchantConsentsErrorTypeEnum;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantConsentsResponse =
  | MerchantConsentsFailureResponse
  | MerchantConsentsSuccessResponse;

export type MerchantConsentsSuccessResponse = {
  __typename?: 'MerchantConsentsSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum MerchantConsentsTypeEnum {
  /** Indicates Digilocker consent type */
  AADHAAR_EKYC_TERMS_AND_CONDITIONS = 'AADHAAR_EKYC_TERMS_AND_CONDITIONS',
  /** Indicates Website Policy consent type */
  POLICY_CREATION_TERMS = 'POLICY_CREATION_TERMS',
  /** Indicates L2 consent type */
  PRIVACY_POLICY = 'PRIVACY_POLICY',
  /** Indicates L2 consent type */
  TERMS_OF_SERVICE = 'TERMS_OF_SERVICE',
}

export type MerchantContact = {
  __typename?: 'MerchantContact';
  active: Scalars['Boolean'];
  createdAt: Scalars['DateTime'];
  email?: Maybe<Scalars['EmailAddress']>;
  fundAccounts?: Maybe<MerchantContactFundAccountsResponse>;
  id: Scalars['ID'];
  name: Scalars['String'];
  notes?: Maybe<Scalars['JSONObject']>;
  paymentTerm?: Maybe<PaymentTerm>;
  phone?: Maybe<Phone>;
  referenceId?: Maybe<Scalars['String']>;
  tdsCategory?: Maybe<TdsCategory>;
  type?: Maybe<Scalars['String']>;
};

export type MerchantContactFundAccountsArgs = {
  limit?: InputMaybe<Scalars['PositiveInt']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
};

export type MerchantContactCreateResponse = MutationResponseInterface & {
  __typename?: 'MerchantContactCreateResponse';
  code: Scalars['PositiveInt'];
  merchantContact?: Maybe<MerchantContact>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantContactEmailOtpSendFailureResponse = MutationResponseInterface & {
  __typename?: 'MerchantContactEmailOtpSendFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantContactEmailOtpSendResponse =
  | MerchantContactEmailOtpSendFailureResponse
  | MerchantContactEmailOtpSendSuccessResponse;

export type MerchantContactEmailOtpSendSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantContactEmailOtpSendSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  otpVerificationToken: Scalars['String'];
  success: Scalars['Boolean'];
};

export type MerchantContactFundAccount = {
  __typename?: 'MerchantContactFundAccount';
  active: Scalars['Boolean'];
  batchId?: Maybe<Scalars['String']>;
  contact: MerchantContact;
  createdAt: Scalars['DateTime'];
  details?: Maybe<MerchantContactFundAccountDetails>;
  id: Scalars['ID'];
  type: MerchantContactFundAccountTypeEnum;
};

export type MerchantContactFundAccountBankAccountInput = {
  accountNumber: Scalars['String'];
  ifsc: Scalars['String'];
  name: Scalars['String'];
};

export type MerchantContactFundAccountCreateResponse = MutationResponseInterface & {
  __typename?: 'MerchantContactFundAccountCreateResponse';
  code: Scalars['PositiveInt'];
  fundAccount: MerchantContactFundAccount;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantContactFundAccountDetails =
  | MerchantContactFundAccountDetailsBankAccount
  | MerchantContactFundAccountDetailsCard
  | MerchantContactFundAccountDetailsVpa
  | MerchantContactFundAccountDetailsWallet;

export type MerchantContactFundAccountDetailsBankAccount = {
  __typename?: 'MerchantContactFundAccountDetailsBankAccount';
  bankName?: Maybe<Scalars['String']>;
  holderName: Scalars['String'];
  ifsc: Scalars['String'];
  number: Scalars['String'];
};

export type MerchantContactFundAccountDetailsCard = {
  __typename?: 'MerchantContactFundAccountDetailsCard';
  issuerName: Scalars['String'];
  last4Digits: Scalars['String'];
  name: Scalars['String'];
  network: Scalars['String'];
  type: Scalars['String'];
};

export type MerchantContactFundAccountDetailsVpa = {
  __typename?: 'MerchantContactFundAccountDetailsVPA';
  address: Scalars['VPA'];
  handle?: Maybe<Scalars['String']>;
  name?: Maybe<Scalars['String']>;
};

export type MerchantContactFundAccountDetailsWallet = {
  __typename?: 'MerchantContactFundAccountDetailsWallet';
  email?: Maybe<Scalars['EmailAddress']>;
  name?: Maybe<Scalars['String']>;
  phone: Phone;
  provider: Scalars['String'];
};

export enum MerchantContactFundAccountTypeEnum {
  BANK_ACCOUNT = 'BANK_ACCOUNT',
  CARD = 'CARD',
  VPA = 'VPA',
  WALLET = 'WALLET',
}

export type MerchantContactFundAccountVpaInput = {
  address: Scalars['VPA'];
};

export type MerchantContactFundAccountsResponse = PaginationResponseInterface & {
  __typename?: 'MerchantContactFundAccountsResponse';
  fundAccounts?: Maybe<Array<MerchantContactFundAccount>>;
  hasMore: Scalars['Boolean'];
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  total?: Maybe<Scalars['NonNegativeInt']>;
};

export type MerchantContactPerson = {
  __typename?: 'MerchantContactPerson';
  email: MerchantEmailField;
  name: MerchantStringField;
  phone: MerchantPhoneField;
};

export type MerchantContactPersonInput = {
  email?: InputMaybe<MerchantEmailInputField>;
  name?: InputMaybe<MerchantStringInputField>;
  phone?: InputMaybe<MerchantPhoneInputField>;
};

export type MerchantContactTypeCreateResponse =
  | MerchantContactTypeCreateResponseDuplicate
  | MerchantContactTypeCreateResponseSuccess;

export type MerchantContactTypeCreateResponseDuplicate = {
  __typename?: 'MerchantContactTypeCreateResponseDuplicate';
  message: Scalars['String'];
};

export type MerchantContactTypeCreateResponseSuccess = {
  __typename?: 'MerchantContactTypeCreateResponseSuccess';
  message: Scalars['String'];
};

export type MerchantContactUpdateResponse = MutationResponseInterface & {
  __typename?: 'MerchantContactUpdateResponse';
  code: Scalars['PositiveInt'];
  merchantContact: MerchantContact;
  message: Scalars['String'];
  success: Scalars['Boolean'];
};

export type MerchantContactsResponse = PaginationResponseInterface & {
  __typename?: 'MerchantContactsResponse';
  hasMore: Scalars['Boolean'];
  limit: Scalars['PositiveInt'];
  merchantContacts: Array<MerchantContact>;
  offset: Scalars['NonNegativeInt'];
  total?: Maybe<Scalars['NonNegativeInt']>;
};

export type MerchantCreditBalance = {
  __typename?: 'MerchantCreditBalance';
  amountCredits: Money;
  balanceId: Scalars['ID'];
  feeCredits: Money;
  refundCredits: Money;
  updatedAt?: Maybe<Scalars['DateTime']>;
};

export type MerchantCreditBalanceFailureResponse = {
  __typename?: 'MerchantCreditBalanceFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantCreditBalanceResponse =
  | MerchantCreditBalanceFailureResponse
  | MerchantCreditBalanceSuccessResponse;

export type MerchantCreditBalanceSuccessResponse = {
  __typename?: 'MerchantCreditBalanceSuccessResponse';
  balanceDetails: MerchantCreditBalance;
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantDocument = {
  __typename?: 'MerchantDocument';
  aadharBack: MerchantDocumentField;
  aadharFront: MerchantDocumentField;
  affiliationCertificate: MerchantDocumentField;
  amfiCertificate: MerchantDocumentField;
  ayushCertificate: MerchantDocumentField;
  bankStatement: MerchantDocumentField;
  bankVerificationLetter: MerchantDocumentField;
  barCouncilCertificate: MerchantDocumentField;
  bbpsDocument: MerchantDocumentField;
  bisCertificate: MerchantDocumentField;
  boardResolutionLetter: MerchantDocumentField;
  brandTieUpDocument: MerchantDocumentField;
  businessCorrespondentDocument: MerchantDocumentField;
  businessPan: MerchantDocumentField;
  businessRegistration: MerchantDocumentField;
  cancelledCheque: MerchantDocumentField;
  cancelledChequeVideo: MerchantDocumentField;
  copywriteLicense: MerchantDocumentField;
  cpvReport: MerchantDocumentField;
  dealershipRightsCertificate: MerchantDocumentField;
  dgcaCertificate: MerchantDocumentField;
  domainOwnershipDocument: MerchantDocumentField;
  dotCertificate: MerchantDocumentField;
  driverLicenseBack: MerchantDocumentField;
  driverLicenseFront: MerchantDocumentField;
  epfSchemeCertificate: MerchantDocumentField;
  fdaCertificate: MerchantDocumentField;
  fdaLicense: MerchantDocumentField;
  ffmcLicense: MerchantDocumentField;
  form8a: MerchantDocumentField;
  form10ac: MerchantDocumentField;
  form12a: MerchantDocumentField;
  form80g: MerchantDocumentField;
  form2020b2121b: MerchantDocumentField;
  fssaiCertificate: MerchantDocumentField;
  fssaiLicense: MerchantDocumentField;
  giaCertificate: MerchantDocumentField;
  giiCertificate: MerchantDocumentField;
  govtAuthorisationLetter: MerchantDocumentField;
  gstCertificate: MerchantDocumentField;
  iataCertificate: MerchantDocumentField;
  iatoLicense: MerchantDocumentField;
  iecLicense: MerchantDocumentField;
  invoice: MerchantDocumentField;
  irctcAgentAgreement: MerchantDocumentField;
  irdaCertificate: MerchantDocumentField;
  irdaiRegistrationCertificate: MerchantDocumentField;
  legalOpinionDocument: MerchantDocumentField;
  liquorLicense: MerchantDocumentField;
  manufacturingLicense: MerchantDocumentField;
  merchantServiceAgreement: MerchantDocumentField;
  mmtcPampLicense: MerchantDocumentField;
  msmeCertificate: MerchantDocumentField;
  msoDocument: MerchantDocumentField;
  nationalHousingBankCertificate: MerchantDocumentField;
  nbfcRegistrationCertificate: MerchantDocumentField;
  passportBack: MerchantDocumentField;
  passportFront: MerchantDocumentField;
  pciDssCertificate: MerchantDocumentField;
  personalPan: MerchantDocumentField;
  /** @deprecated Use pesoLicense instead */
  pescoLicense: MerchantDocumentField;
  pesoLicense: MerchantDocumentField;
  pharmacyDrugLicense: MerchantDocumentField;
  pmWaniCertificate: MerchantDocumentField;
  ppiLicense: MerchantDocumentField;
  proofOfProfession: MerchantDocumentField;
  rbiCertificate: MerchantDocumentField;
  reraLicense: MerchantDocumentField;
  resellerAgreement: MerchantDocumentField;
  safegoldPartnershipDocument: MerchantDocumentField;
  sebiRegistrationCertificate: MerchantDocumentField;
  shopEstablishmentCertificate: MerchantDocumentField;
  slaAmfiCertificate: MerchantDocumentField;
  slaDealershipAgreement: MerchantDocumentField;
  slaDocument: MerchantDocumentField;
  slaFfmcLicense: MerchantDocumentField;
  slaIataCertificate: MerchantDocumentField;
  slaIrdaiRegistrationCertificate: MerchantDocumentField;
  slaNbfcRegistrationCertificate: MerchantDocumentField;
  slaSebiRegistrationCertificate: MerchantDocumentField;
  tradeLicense: MerchantDocumentField;
  traiCertificate: MerchantDocumentField;
  undertaking: MerchantDocumentField;
  undertakingDocument: MerchantDocumentField;
  voterIdBack: MerchantDocumentField;
  voterIdFront: MerchantDocumentField;
};

export type MerchantDocumentByIdResponse = MerchantDocumentFieldValueInterface & {
  __typename?: 'MerchantDocumentByIdResponse';
  code: Scalars['PositiveInt'];
  createdAt: Scalars['DateTime'];
  fileName?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  message?: Maybe<Scalars['String']>;
  signedUrl?: Maybe<Scalars['URL']>;
  success: Scalars['Boolean'];
};

export type MerchantDocumentField = MerchantFieldInterface & {
  __typename?: 'MerchantDocumentField';
  clarificationReasons: Array<MerchantFieldClarificationReason>;
  values: Array<MerchantDocumentFieldValue>;
  verificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantDocumentFieldValue = MerchantDocumentFieldValueInterface & {
  __typename?: 'MerchantDocumentFieldValue';
  createdAt?: Maybe<Scalars['DateTime']>;
  fileName?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  /** @deprecated Unused */
  url?: Maybe<Scalars['URL']>;
};

export type MerchantDocumentFieldValueInterface = {
  createdAt?: Maybe<Scalars['DateTime']>;
  fileName?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
};

export type MerchantDocumentInput = {
  aadharBack?: InputMaybe<MerchantDocumentInputField>;
  aadharFront?: InputMaybe<MerchantDocumentInputField>;
  affiliationCertificate?: InputMaybe<MerchantDocumentInputField>;
  amfiCertificate?: InputMaybe<MerchantDocumentInputField>;
  ayushCertificate?: InputMaybe<MerchantDocumentInputField>;
  bankStatement?: InputMaybe<MerchantDocumentInputField>;
  bankVerificationLetter?: InputMaybe<MerchantDocumentInputField>;
  barCouncilCertificate?: InputMaybe<MerchantDocumentInputField>;
  bbpsDocument?: InputMaybe<MerchantDocumentInputField>;
  bisCertificate?: InputMaybe<MerchantDocumentInputField>;
  boardResolutionLetter?: InputMaybe<MerchantDocumentInputField>;
  brandTieUpDocument?: InputMaybe<MerchantDocumentInputField>;
  businessCorrespondentDocument?: InputMaybe<MerchantDocumentInputField>;
  businessPan?: InputMaybe<MerchantDocumentInputField>;
  businessRegistration?: InputMaybe<MerchantDocumentInputField>;
  cancelledCheque?: InputMaybe<MerchantDocumentInputField>;
  cancelledChequeVideo?: InputMaybe<MerchantDocumentInputField>;
  copywriteLicense?: InputMaybe<MerchantDocumentInputField>;
  cpvReport?: InputMaybe<MerchantDocumentInputField>;
  dealershipRightsCertificate?: InputMaybe<MerchantDocumentInputField>;
  dgcaCertificate?: InputMaybe<MerchantDocumentInputField>;
  domainOwnershipDocument?: InputMaybe<MerchantDocumentInputField>;
  dotCertificate?: InputMaybe<MerchantDocumentInputField>;
  driverLicenseBack?: InputMaybe<MerchantDocumentInputField>;
  driverLicenseFront?: InputMaybe<MerchantDocumentInputField>;
  epfSchemeCertificate?: InputMaybe<MerchantDocumentInputField>;
  fdaCertificate?: InputMaybe<MerchantDocumentInputField>;
  fdaLicense?: InputMaybe<MerchantDocumentInputField>;
  ffmcLicense?: InputMaybe<MerchantDocumentInputField>;
  form8a?: InputMaybe<MerchantDocumentInputField>;
  form10ac?: InputMaybe<MerchantDocumentInputField>;
  form12a?: InputMaybe<MerchantDocumentInputField>;
  form80g?: InputMaybe<MerchantDocumentInputField>;
  form2020b2121b?: InputMaybe<MerchantDocumentInputField>;
  fssaiCertificate?: InputMaybe<MerchantDocumentInputField>;
  fssaiLicense?: InputMaybe<MerchantDocumentInputField>;
  giaCertificate?: InputMaybe<MerchantDocumentInputField>;
  giiCertificate?: InputMaybe<MerchantDocumentInputField>;
  govtAuthorisationLetter?: InputMaybe<MerchantDocumentInputField>;
  gstCertificate?: InputMaybe<MerchantDocumentInputField>;
  iataCertificate?: InputMaybe<MerchantDocumentInputField>;
  iatoLicense?: InputMaybe<MerchantDocumentInputField>;
  iecLicense?: InputMaybe<MerchantDocumentInputField>;
  invoice?: InputMaybe<MerchantDocumentInputField>;
  irctcAgentAgreement?: InputMaybe<MerchantDocumentInputField>;
  irdaCertificate?: InputMaybe<MerchantDocumentInputField>;
  irdaiRegistrationCertificate?: InputMaybe<MerchantDocumentInputField>;
  legalOpinionDocument?: InputMaybe<MerchantDocumentInputField>;
  liquorLicense?: InputMaybe<MerchantDocumentInputField>;
  manufacturingLicense?: InputMaybe<MerchantDocumentInputField>;
  merchantServiceAgreement?: InputMaybe<MerchantDocumentInputField>;
  mmtcPampLicense?: InputMaybe<MerchantDocumentInputField>;
  msmeCertificate?: InputMaybe<MerchantDocumentInputField>;
  msoDocument?: InputMaybe<MerchantDocumentInputField>;
  nationalHousingBankCertificate?: InputMaybe<MerchantDocumentInputField>;
  nbfcRegistrationCertificate?: InputMaybe<MerchantDocumentInputField>;
  passportBack?: InputMaybe<MerchantDocumentInputField>;
  passportFront?: InputMaybe<MerchantDocumentInputField>;
  pciDssCertificate?: InputMaybe<MerchantDocumentInputField>;
  personalPan?: InputMaybe<MerchantDocumentInputField>;
  /**
   * pescoLicense is deprecated use pesoLicense instead,
   * not deprecating using @deprecate because in type input
   * deprecation does not generate types for the deprecated
   * input field
   */
  pescoLicense?: InputMaybe<MerchantDocumentInputField>;
  pesoLicense?: InputMaybe<MerchantDocumentInputField>;
  pharmacyDrugLicense?: InputMaybe<MerchantDocumentInputField>;
  pmWaniCertificate?: InputMaybe<MerchantDocumentInputField>;
  ppiLicense?: InputMaybe<MerchantDocumentInputField>;
  proofOfProfession?: InputMaybe<MerchantDocumentInputField>;
  rbiCertificate?: InputMaybe<MerchantDocumentInputField>;
  reraLicense?: InputMaybe<MerchantDocumentInputField>;
  resellerAgreement?: InputMaybe<MerchantDocumentInputField>;
  safegoldPartnershipDocument?: InputMaybe<MerchantDocumentInputField>;
  sebiRegistrationCertificate?: InputMaybe<MerchantDocumentInputField>;
  shopEstablishmentCertificate?: InputMaybe<MerchantDocumentInputField>;
  slaAmfiCertificate?: InputMaybe<MerchantDocumentInputField>;
  slaDealershipAgreement?: InputMaybe<MerchantDocumentInputField>;
  slaDocument?: InputMaybe<MerchantDocumentInputField>;
  slaFfmcLicense?: InputMaybe<MerchantDocumentInputField>;
  slaIataCertificate?: InputMaybe<MerchantDocumentInputField>;
  slaIrdaiRegistrationCertificate?: InputMaybe<MerchantDocumentInputField>;
  slaNbfcRegistrationCertificate?: InputMaybe<MerchantDocumentInputField>;
  slaSebiRegistrationCertificate?: InputMaybe<MerchantDocumentInputField>;
  tradeLicense?: InputMaybe<MerchantDocumentInputField>;
  traiCertificate?: InputMaybe<MerchantDocumentInputField>;
  undertaking?: InputMaybe<MerchantDocumentInputField>;
  undertakingDocument?: InputMaybe<MerchantDocumentInputField>;
  voterIdBack?: InputMaybe<MerchantDocumentInputField>;
  voterIdFront?: InputMaybe<MerchantDocumentInputField>;
};

export type MerchantDocumentInputField = {
  clarificationReasons?: InputMaybe<Array<MerchantClarificationInputType>>;
  value?: InputMaybe<Scalars['String']>;
};

export type MerchantDocumentUpload = {
  __typename?: 'MerchantDocumentUpload';
  createdAt: Scalars['DateTime'];
  displayName: Scalars['String'];
  id: Scalars['ID'];
  mimeType?: Maybe<Scalars['String']>;
  purpose: MerchantDocumentUploadPurposeEnum;
  size: Scalars['PositiveInt'];
};

export type MerchantDocumentUploadFailureResponse = {
  __typename?: 'MerchantDocumentUploadFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum MerchantDocumentUploadPurposeEnum {
  MERCHANT_WORKFLOW_CLARIFICATION = 'MERCHANT_WORKFLOW_CLARIFICATION',
}

export type MerchantDocumentUploadResponse =
  | MerchantDocumentUploadFailureResponse
  | MerchantDocumentUploadSuccessResponse;

export type MerchantDocumentUploadSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantDocumentUploadSuccessResponse';
  code: Scalars['PositiveInt'];
  documentUpload: MerchantDocumentUpload;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantEmailField = MerchantFieldInterface & {
  __typename?: 'MerchantEmailField';
  clarificationReasons: Array<MerchantFieldClarificationReason>;
  value?: Maybe<Scalars['EmailAddress']>;
  verificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantEmailInputField = {
  clarificationReasons?: InputMaybe<Array<MerchantClarificationInputType>>;
  value?: InputMaybe<Scalars['EmailAddress']>;
};

export type MerchantEmailUpdateFailureResponse = {
  __typename?: 'MerchantEmailUpdateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantEmailUpdateResponse =
  | MerchantEmailUpdateFailureResponse
  | MerchantEmailUpdateSuccessResponse;

export type MerchantEmailUpdateSuccessResponse = {
  __typename?: 'MerchantEmailUpdateSuccessResponse';
  code: Scalars['PositiveInt'];
  isExistingUser: Scalars['Boolean'];
  isOwner: Scalars['Boolean'];
  isTeamMember: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantEscalationAction = {
  __typename?: 'MerchantEscalationAction';
  description?: Maybe<Scalars['String']>;
  status?: Maybe<Scalars['String']>;
};

export type MerchantEscalationLimit = {
  __typename?: 'MerchantEscalationLimit';
  milestone: MerchantActivationMilestoneEnum;
  threshold: Money;
};

export enum MerchantEscalationTypeEnum {
  PAYMENT_BREACH = 'PAYMENT_BREACH',
  SETTLEMENT_BREACH = 'SETTLEMENT_BREACH',
}

export type MerchantEscalations =
  | MerchantActivationEscalationsBreached
  | MerchantActivationEscalationsNotBreached;

export type MerchantFeatureFlag = {
  __typename?: 'MerchantFeatureFlag';
  displayText?: Maybe<Scalars['String']>;
  isEnabled?: Maybe<Scalars['Boolean']>;
  name: Scalars['String'];
};

export type MerchantFeeBasedGating = {
  __typename?: 'MerchantFeeBasedGating';
  isEligible: Scalars['Boolean'];
  orderId?: Maybe<Scalars['String']>;
  paymentStatus?: Maybe<FeeBasedGatingPaymentStatusEnum>;
};

export type MerchantFieldClarificationReason = {
  __typename?: 'MerchantFieldClarificationReason';
  comment: Scalars['String'];
  count: Scalars['PositiveInt'];
  createdAt: Scalars['DateTime'];
  isCurrentClarification: Scalars['Boolean'];
  sender: MerchantClarificationFromEnum;
  type: MerchantClarificationTypeEnum;
};

export type MerchantFieldInterface = {
  clarificationReasons: Array<MerchantFieldClarificationReason>;
  verificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantGstinUpdate = {
  __typename?: 'MerchantGstinUpdate';
  gstin: Scalars['String'];
  gstinCertificateFileId: Scalars['String'];
  gstinCertificateId: Scalars['String'];
  isGstinAddOperation: Scalars['Boolean'];
  isSyncFlow: Scalars['Boolean'];
  isWorkFlowCreated?: Maybe<Scalars['Boolean']>;
  validationId: Scalars['ID'];
};

export type MerchantGstinUpdateAsyncFlowSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantGstinUpdateAsyncFlowSuccessResponse';
  code: Scalars['PositiveInt'];
  gstinUpdate: MerchantGstinUpdate;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum MerchantGstinUpdateCustomerActionEnum {
  AWAITING_CUSTOMER_RESPONSE = 'AWAITING_CUSTOMER_RESPONSE',
  CUSTOMER_RESPONDED = 'CUSTOMER_RESPONDED',
}

export type MerchantGstinUpdateFailureResponse = {
  __typename?: 'MerchantGstinUpdateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantGstinUpdateInSyncFlowResponse = MutationResponseInterface & {
  __typename?: 'MerchantGstinUpdateInSyncFlowResponse';
  code: Scalars['PositiveInt'];
  gstinUpdate: MerchantGstinUpdate;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantGstinUpdateInSyncWorkFlowCreatedResponse = MutationResponseInterface & {
  __typename?: 'MerchantGstinUpdateInSyncWorkFlowCreatedResponse';
  code: Scalars['PositiveInt'];
  gstinUpdate: MerchantGstinUpdate;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantGstinUpdateResponse =
  | MerchantGstinUpdateAsyncFlowSuccessResponse
  | MerchantGstinUpdateFailureResponse
  | MerchantGstinUpdateInSyncFlowResponse
  | MerchantGstinUpdateInSyncWorkFlowCreatedResponse;

export enum MerchantGstinWorkflowStatusEnum {
  APPROVED = 'APPROVED',
  CLOSED = 'CLOSED',
  EXECUTED = 'EXECUTED',
  FAILED = 'FAILED',
  OPEN = 'OPEN',
  REJECTED = 'REJECTED',
}

export type MerchantIdentityResponse = {
  __typename?: 'MerchantIdentityResponse';
  businessName: Scalars['String'];
  number: Scalars['String'];
  type: MerchantIdentityTypeEnum;
};

export enum MerchantIdentityTypeEnum {
  COMPANY_IDENTIFICATION_NUMBER = 'COMPANY_IDENTIFICATION_NUMBER',
  LIMITED_LIABILITY_PARTNERSHIP_IDENTIFICATION_NUMBER = 'LIMITED_LIABILITY_PARTNERSHIP_IDENTIFICATION_NUMBER',
}

export enum MerchantKycPartnerAccessErrorTypeEnum {
  EMPTY_RESPONSE = 'EMPTY_RESPONSE',
  SERVER_ERROR = 'SERVER_ERROR',
}

export enum MerchantKycPartnerAccessInputTypeEnum {
  APPROVED = 'APPROVED',
  REJECTED = 'REJECTED',
}

export type MerchantKycPartnerAccessResponse = {
  __typename?: 'MerchantKYCPartnerAccessResponse';
  partnerName: Scalars['String'];
  status: MerchantKycPartnerAccessTypeEnum;
};

export type MerchantKycPartnerAccessStatusUpdateResponse =
  | MerchantKycPartnerAccessUpdateFailureResponse
  | MerchantKycPartnerAccessUpdateSuccessResponse;

export enum MerchantKycPartnerAccessTypeEnum {
  APPROVED = 'APPROVED',
  PENDING = 'PENDING',
  REJECTED = 'REJECTED',
}

export type MerchantKycPartnerAccessUpdateFailureResponse = {
  __typename?: 'MerchantKYCPartnerAccessUpdateFailureResponse';
  code: Scalars['PositiveInt'];
  errorCode?: Maybe<MerchantKycPartnerAccessErrorTypeEnum>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantKycPartnerAccessUpdateSuccessResponse = {
  __typename?: 'MerchantKYCPartnerAccessUpdateSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum MerchantMonthlyRevenueEnum {
  FIFTY_LAKHS_TO_ONE_CRORE = 'FIFTY_LAKHS_TO_ONE_CRORE',
  FIVE_LACS_TO_TWENTY_FIVE_LAKHS = 'FIVE_LACS_TO_TWENTY_FIVE_LAKHS',
  LESS_THAN_FIVE_LAKHS = 'LESS_THAN_FIVE_LAKHS',
  MORE_THAN_ONE_CRORE = 'MORE_THAN_ONE_CRORE',
  NOT_PROCESSING = 'NOT_PROCESSING',
  TWENTY_FIVE_LAKHS_TO_FIFTY_LAKHS = 'TWENTY_FIVE_LAKHS_TO_FIFTY_LAKHS',
}

export type MerchantName = {
  __typename?: 'MerchantName';
  billing?: Maybe<Scalars['String']>;
  display?: Maybe<Scalars['String']>;
  registered?: Maybe<Scalars['String']>;
};

export type MerchantNcEligibilityResponse = MutationResponseInterface & {
  __typename?: 'MerchantNcEligibilityResponse';
  code: Scalars['PositiveInt'];
  isNeedsClarificationRevampEnabled: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantNumberField = MerchantFieldInterface & {
  __typename?: 'MerchantNumberField';
  clarificationReasons: Array<MerchantFieldClarificationReason>;
  value?: Maybe<Scalars['PositiveInt']>;
  verificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantOnboardingConfig = {
  __typename?: 'MerchantOnboardingConfig';
  configData: ConfigData;
  configType: Scalars['String'];
  success: Scalars['Boolean'];
};

export type MerchantOnboardingConfigurationInput = {
  couponPopupCount?: InputMaybe<Scalars['PositiveInt']>;
  isMtuCouponCongratulatoryPopupEnabled?: InputMaybe<Scalars['Boolean']>;
  namespace: MerchantConfigurationNamespaceEnum;
  referralSuccessPopupCount?: InputMaybe<Scalars['NonNegativeInt']>;
  showFtuxFinalScreen?: InputMaybe<Scalars['Boolean']>;
  showFtuxFirstPaymentBanner?: InputMaybe<Scalars['Boolean']>;
  upiTerminalProcurementStatus?: InputMaybe<UpiTerminalProcurementStatusEnum>;
  websiteIncompleteSoftNudgeCount?: InputMaybe<Scalars['Int']>;
  websiteIncompleteSoftNudgeTimestamp?: InputMaybe<Scalars['Int']>;
};

export type MerchantOnboardingQuestionDetail = {
  __typename?: 'MerchantOnboardingQuestionDetail';
  answer: Array<Scalars['String']>;
  questionId: Scalars['ID'];
};

export type MerchantOnboardingQuestionDetailInput = {
  answer: Array<Scalars['String']>;
  questionId: Scalars['ID'];
};

export type MerchantOnboardingQuestionDetailsFailureResponse = {
  __typename?: 'MerchantOnboardingQuestionDetailsFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantOnboardingQuestionDetailsResponse =
  | MerchantOnboardingQuestionDetailsFailureResponse
  | MerchantOnboardingQuestionDetailsSuccessResponse;

export type MerchantOnboardingQuestionDetailsSuccessResponse = {
  __typename?: 'MerchantOnboardingQuestionDetailsSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  questionDetails: Array<MerchantOnboardingQuestionDetail>;
  success: Scalars['Boolean'];
};

export type MerchantOnboardingQuestionDetailsUpdateResponse = {
  __typename?: 'MerchantOnboardingQuestionDetailsUpdateResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPaymentAcceptanceChannels = {
  __typename?: 'MerchantPaymentAcceptanceChannels';
  android: MerchantAcceptanceChannel;
  ios: MerchantAcceptanceChannel;
  offlineStore: MerchantAcceptanceChannel;
  others: MerchantAcceptanceChannel;
  socialMedia: MerchantAcceptanceChannel;
  websites: MerchantAcceptanceChannel;
  whatsappSmsEmail: MerchantAcceptanceChannelWhatsappSmsEmail;
};

export type MerchantPaymentAcceptanceChannelsInput = {
  android?: InputMaybe<MerchantAcceptanceChannelInput>;
  ios?: InputMaybe<MerchantAcceptanceChannelInput>;
  offlineStore?: InputMaybe<MerchantAcceptanceChannelInput>;
  others?: InputMaybe<MerchantAcceptanceChannelInput>;
  socialMedia?: InputMaybe<MerchantAcceptanceChannelInput>;
  websites?: InputMaybe<MerchantAcceptanceChannelInput>;
  whatsappSmsEmail?: InputMaybe<MerchantAcceptanceChannelWhatsappSmsEmailInput>;
};

export type MerchantPaymentHandle = {
  __typename?: 'MerchantPaymentHandle';
  paymentHandleSlug: Scalars['String'];
  /** @deprecated paymentPageId is deprecated */
  paymentPageId?: Maybe<Scalars['String']>;
  title: Scalars['String'];
  url: Scalars['URL'];
};

export type MerchantPaymentHandleAvailabilityFailureResponse = {
  __typename?: 'MerchantPaymentHandleAvailabilityFailureResponse';
  /** Similar to HTTP status code, represents the status of the query */
  code: Scalars['PositiveInt'];
  /** Human-readable error or success message for the UI */
  message?: Maybe<Scalars['String']>;
  /** Indicates whether request was successfull or not */
  success: Scalars['Boolean'];
};

export type MerchantPaymentHandleAvailabilityResponse =
  | MerchantPaymentHandleAvailabilityFailureResponse
  | MerchantPaymentHandleAvailabilitySuccessResponse;

export type MerchantPaymentHandleAvailabilitySuccessResponse = {
  __typename?: 'MerchantPaymentHandleAvailabilitySuccessResponse';
  /** Similar to HTTP status code, represents the status of the query */
  code: Scalars['PositiveInt'];
  /** Indicates whether payment handle is available or not */
  isPaymentHandleAvailable: Scalars['Boolean'];
  /** Human-readable error or success message for the UI */
  message?: Maybe<Scalars['String']>;
  /** Indicates whether request was successfull or not */
  success: Scalars['Boolean'];
};

export type MerchantPaymentHandleCreateFailureResponse = {
  __typename?: 'MerchantPaymentHandleCreateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPaymentHandleCreateResponse =
  | MerchantPaymentHandleCreateFailureResponse
  | MerchantPaymentHandleCreateSuccessResponse;

export type MerchantPaymentHandleCreateSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantPaymentHandleCreateSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  paymentHandle: MerchantPaymentHandle;
  success: Scalars['Boolean'];
};

export type MerchantPaymentHandleEncryptedAmountFailureResponse = {
  __typename?: 'MerchantPaymentHandleEncryptedAmountFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPaymentHandleEncryptedAmountResponse =
  | MerchantPaymentHandleEncryptedAmountFailureResponse
  | MerchantPaymentHandleEncryptedAmountSuccessResponse;

export type MerchantPaymentHandleEncryptedAmountSuccessResponse = {
  __typename?: 'MerchantPaymentHandleEncryptedAmountSuccessResponse';
  code: Scalars['PositiveInt'];
  encryptedAmount: Scalars['String'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPaymentHandleFailureResponse = {
  __typename?: 'MerchantPaymentHandleFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPaymentHandleResponse =
  | MerchantPaymentHandleFailureResponse
  | MerchantPaymentHandleSuccessResponse;

export type MerchantPaymentHandleSuccessResponse = {
  __typename?: 'MerchantPaymentHandleSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  paymentHandle: MerchantPaymentHandle;
  success: Scalars['Boolean'];
};

export type MerchantPaymentHandleSuggestionsResponse = {
  __typename?: 'MerchantPaymentHandleSuggestionsResponse';
  /** Suggestions for the payment handle a merchant can use */
  suggestions: Array<Scalars['String']>;
};

export type MerchantPaymentHandleUpdateFailureResponse = {
  __typename?: 'MerchantPaymentHandleUpdateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPaymentHandleUpdateResponse =
  | MerchantPaymentHandleUpdateFailureResponse
  | MerchantPaymentHandleUpdateSuccessResponse;

export type MerchantPaymentHandleUpdateSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantPaymentHandleUpdateSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  paymentHandle: MerchantPaymentHandle;
  success: Scalars['Boolean'];
};

export type MerchantPhoneField = MerchantFieldInterface & {
  __typename?: 'MerchantPhoneField';
  clarificationReasons: Array<MerchantFieldClarificationReason>;
  value: Phone;
  verificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantPhoneInputField = {
  clarificationReasons?: InputMaybe<Array<MerchantClarificationInputType>>;
  value?: InputMaybe<PhoneInput>;
};

export type MerchantPolicyEmptyPreviewResponse = {
  __typename?: 'MerchantPolicyEmptyPreviewResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPolicyEmptyResponse = {
  __typename?: 'MerchantPolicyEmptyResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPolicyEmptyV2PreviewResponse = {
  __typename?: 'MerchantPolicyEmptyV2PreviewResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPolicyFailureResponse = {
  __typename?: 'MerchantPolicyFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPolicyPreview = {
  __typename?: 'MerchantPolicyPreview';
  html: Scalars['String'];
  section: MerchantWebsiteSectionEnum;
};

export type MerchantPolicyPreviewFailureResponse = {
  __typename?: 'MerchantPolicyPreviewFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPolicyPreviewResponse =
  | MerchantPolicyEmptyPreviewResponse
  | MerchantPolicyPreviewFailureResponse
  | MerchantPolicyPreviewSuccessResponse;

export type MerchantPolicyPreviewSuccessResponse = {
  __typename?: 'MerchantPolicyPreviewSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  policyPreview: Scalars['String'];
  success: Scalars['Boolean'];
};

export type MerchantPolicyPreviewV2FailureResponse = {
  __typename?: 'MerchantPolicyPreviewV2FailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPolicyPreviewV2Response =
  | MerchantPolicyEmptyV2PreviewResponse
  | MerchantPolicyPreviewV2FailureResponse
  | MerchantPolicyPreviewV2SuccessResponse;

export type MerchantPolicyPreviewV2SuccessResponse = {
  __typename?: 'MerchantPolicyPreviewV2SuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  policyPreview: Array<MerchantPolicyPreview>;
  success: Scalars['Boolean'];
};

export type MerchantPolicyPublishFailureResponse = {
  __typename?: 'MerchantPolicyPublishFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPolicyPublishResponse =
  | MerchantPolicyPublishFailureResponse
  | MerchantPolicyPublishSuccessResponse;

export type MerchantPolicyPublishSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantPolicyPublishSuccessResponse';
  code: Scalars['PositiveInt'];
  merchantWebsite: MerchantWebsite;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPolicyResponse =
  | MerchantPolicyEmptyResponse
  | MerchantPolicyFailureResponse
  | MerchantPolicySuccessResponse;

export type MerchantPolicySuccessResponse = {
  __typename?: 'MerchantPolicySuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  policy: Scalars['String'];
  success: Scalars['Boolean'];
};

export type MerchantPolicyWizardV2EligibilityResponse = MutationResponseInterface & {
  __typename?: 'MerchantPolicyWizardV2EligibilityResponse';
  code: Scalars['PositiveInt'];
  isPolicyWizardV2Enabled: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantPreference = {
  __typename?: 'MerchantPreference';
  createdAt: Scalars['DateTime'];
  group: Scalars['String'];
  id: Scalars['ID'];
  merchantId: Scalars['ID'];
  productType: MerchantPreferenceProductTypeEnum;
  type: Scalars['String'];
  updatedAt: Scalars['DateTime'];
  value: Scalars['String'];
};

export enum MerchantPreferenceProductTypeEnum {
  BANKING = 'BANKING',
  PRIMARY = 'PRIMARY',
}

export type MerchantReferralFailureResponse = {
  __typename?: 'MerchantReferralFailureResponse';
  /** Similar to HTTP status code, represents the status of the query */
  code: Scalars['PositiveInt'];
  /** Human-readable error message for the UI */
  message: Scalars['String'];
};

export type MerchantReferralResponse =
  | MerchantReferralFailureResponse
  | MerchantReferralSuccessResponse;

export type MerchantReferralSuccessResponse = {
  __typename?: 'MerchantReferralSuccessResponse';
  /** Indicates whether the advocate is eligible for referring or not */
  canRefer: Scalars['Boolean'];
  /** Maximum number of times referral amount will be credited for advocate */
  maxAllowedReferrals?: Maybe<Scalars['Int']>;
  /** Amount credit (in paisa) to for each successful referral */
  referralAmount?: Maybe<Money>;
  /** Shareable referral link for a merchant to refer another merchant */
  referralLink?: Maybe<Scalars['String']>;
};

export enum MerchantRoleEnum {
  ADMIN = 'ADMIN',
  AUTH_LINK_AGENT = 'AUTH_LINK_AGENT',
  AUTH_LINK_SUPERVISOR = 'AUTH_LINK_SUPERVISOR',
  FINANCE = 'FINANCE',
  LINKED_ACCOUNT_ADMIN = 'LINKED_ACCOUNT_ADMIN',
  LINKED_ACCOUNT_OWNER = 'LINKED_ACCOUNT_OWNER',
  MANAGER = 'MANAGER',
  OPERATIONS = 'OPERATIONS',
  OWNER = 'OWNER',
  RBL_AGENT = 'RBL_AGENT',
  RBL_SUPERVISOR = 'RBL_SUPERVISOR',
  SELLERAPP = 'SELLERAPP',
  SUPPORT = 'SUPPORT',
  VIEW_ONLY = 'VIEW_ONLY',
}

export enum MerchantSelfServeGstinPermissionEnum {
  EDIT_MERCHANT_BANK_ACCOUNT_DETAIL = 'EDIT_MERCHANT_BANK_ACCOUNT_DETAIL',
  EDIT_MERCHANT_GSTIN_DETAIL = 'EDIT_MERCHANT_GSTIN_DETAIL',
  UPDATE_MERCHANT_GSTIN_DETAIL = 'UPDATE_MERCHANT_GSTIN_DETAIL',
}

export type MerchantSelfServeWorkflow = {
  __typename?: 'MerchantSelfServeWorkflow';
  bankAccountId?: Maybe<Scalars['ID']>;
  createdAt?: Maybe<Scalars['DateTime']>;
  customerActions?: Maybe<Array<Maybe<MerchantGstinUpdateCustomerActionEnum>>>;
  isRequestUnderBvsValidation: Scalars['Boolean'];
  isWorkflowExits: Scalars['Boolean'];
  needsClarificationMessage?: Maybe<Scalars['String']>;
  permission?: Maybe<MerchantSelfServeGstinPermissionEnum>;
  rejectedAt?: Maybe<Scalars['DateTime']>;
  rejectionReason?: Maybe<Scalars['String']>;
  workflowStatus?: Maybe<MerchantGstinWorkflowStatusEnum>;
};

export enum MerchantSelfServeWorkflowEnum {
  MERCHANT_BANK_DETAILS_UPDATE = 'MERCHANT_BANK_DETAILS_UPDATE',
  MERCHANT_UPDATE_GSTIN = 'MERCHANT_UPDATE_GSTIN',
}

export type MerchantSelfServeWorkflowStatusFailureResponse = {
  __typename?: 'MerchantSelfServeWorkflowStatusFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantSelfServeWorkflowStatusResponse =
  | MerchantSelfServeWorkflowStatusFailureResponse
  | MerchantSelfServeWorkflowStatusSuccessResponse;

export type MerchantSelfServeWorkflowStatusSuccessResponse = {
  __typename?: 'MerchantSelfServeWorkflowStatusSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  selfServeWorkflow: MerchantSelfServeWorkflow;
  success: Scalars['Boolean'];
};

export type MerchantSettlementConfigFailureResponse = {
  __typename?: 'MerchantSettlementConfigFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantSettlementConfigResponse =
  | MerchantSettlementConfigFailureResponse
  | MerchantSettlementConfigSuccessResponse;

export type MerchantSettlementConfigSuccessResponse = {
  __typename?: 'MerchantSettlementConfigSuccessResponse';
  code: Scalars['PositiveInt'];
  fundsOnHold: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
  settlementBlocked: Scalars['Boolean'];
  settlementsOnHold: Scalars['Boolean'];
  success: Scalars['Boolean'];
};

export type MerchantShopEstablishment = {
  __typename?: 'MerchantShopEstablishment';
  isVerifiableZone?: Maybe<Scalars['Boolean']>;
  number: MerchantStringField;
};

export type MerchantShopEstablishmentInput = {
  number?: InputMaybe<MerchantStringInputField>;
};

export type MerchantSocialMediaUrlField = {
  __typename?: 'MerchantSocialMediaURLField';
  platform?: Maybe<Scalars['String']>;
  url?: Maybe<Scalars['URL']>;
};

export type MerchantSocialMediaUrlInputField = {
  platform?: InputMaybe<Scalars['String']>;
  url?: InputMaybe<Scalars['URL']>;
};

export type MerchantStakeholder = {
  __typename?: 'MerchantStakeholder';
  aadharEsignStatus?: Maybe<MerchantVerificationStatusEnum>;
  aadharPin?: Maybe<Scalars['String']>;
  isAadharLinked?: Maybe<Scalars['Boolean']>;
  name: MerchantStringField;
  pan: MerchantStringField;
  panVerificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantStakeholderInput = {
  isAadharLinked?: InputMaybe<Scalars['Boolean']>;
  name?: InputMaybe<MerchantStringInputField>;
  pan?: InputMaybe<MerchantStringInputField>;
};

export type MerchantStringField = MerchantFieldInterface & {
  __typename?: 'MerchantStringField';
  clarificationReasons: Array<MerchantFieldClarificationReason>;
  suggestedValue?: Maybe<Scalars['String']>;
  value?: Maybe<Scalars['String']>;
  verificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantStringInputField = {
  clarificationReasons?: InputMaybe<Array<MerchantClarificationInputType>>;
  value?: InputMaybe<Scalars['String']>;
};

export type MerchantSupportDetails = {
  __typename?: 'MerchantSupportDetails';
  email?: Maybe<Scalars['EmailAddress']>;
  phone?: Maybe<Phone>;
  websiteUrl?: Maybe<Scalars['URL']>;
};

export type MerchantSupportDetailsFailureResponse = {
  __typename?: 'MerchantSupportDetailsFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantSupportDetailsResponse =
  | MerchantSupportDetailsFailureResponse
  | MerchantSupportDetailsSuccessResponse;

export type MerchantSupportDetailsSuccessResponse = {
  __typename?: 'MerchantSupportDetailsSuccessResponse';
  code: Scalars['PositiveInt'];
  details?: Maybe<MerchantSupportDetails>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantSwitchResponse = MutationResponseInterface & {
  __typename?: 'MerchantSwitchResponse';
  code: Scalars['PositiveInt'];
  message: Scalars['String'];
  success: Scalars['Boolean'];
};

export type MerchantTransactionLimit = {
  __typename?: 'MerchantTransactionLimit';
  paymentLimit?: Maybe<Money>;
  settlementLimit: Money;
};

export type MerchantUrlField = MerchantFieldInterface & {
  __typename?: 'MerchantURLField';
  clarificationReasons: Array<MerchantFieldClarificationReason>;
  value?: Maybe<Scalars['URL']>;
  verificationStatus?: Maybe<MerchantVerificationStatusEnum>;
};

export type MerchantUrlInputField = {
  clarificationReasons?: InputMaybe<Array<MerchantClarificationInputType>>;
  value?: InputMaybe<Scalars['URL']>;
};

export type MerchantValidateSocialMediaUrlResponse = {
  __typename?: 'MerchantValidateSocialMediaURLResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum MerchantVerificationStatusEnum {
  FAILED = 'FAILED',
  INCORRECT_DETAILS = 'INCORRECT_DETAILS',
  INITIATED = 'INITIATED',
  NOT_MATCHED = 'NOT_MATCHED',
  NOT_VERIFIED = 'NOT_VERIFIED',
  PENDING = 'PENDING',
  VERIFIED = 'VERIFIED',
}

export type MerchantVirtualAccount = {
  __typename?: 'MerchantVirtualAccount';
  id: Scalars['ID'];
  receivers: Array<MerchantVirtualAccountReceiver>;
  status: MerchantVirtualAccountStatus;
};

export type MerchantVirtualAccountReceiver = {
  __typename?: 'MerchantVirtualAccountReceiver';
  id: Scalars['ID'];
  ifsc: Scalars['String'];
  name: Scalars['String'];
  number: Scalars['String'];
};

export enum MerchantVirtualAccountStatus {
  ACTIVE = 'ACTIVE',
  CLOSED = 'CLOSED',
  PAID = 'PAID',
}

export type MerchantVirtualAccountsResponse = PaginationResponseInterface & {
  __typename?: 'MerchantVirtualAccountsResponse';
  hasMore: Scalars['Boolean'];
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  total?: Maybe<Scalars['NonNegativeInt']>;
  virtualAccounts: Array<MerchantVirtualAccount>;
};

export type MerchantWebsite = {
  __typename?: 'MerchantWebsite';
  additionalData?: Maybe<MerchantWebsiteAdditionalData>;
  contactUs?: Maybe<MerchantWebsiteSection>;
  privacy?: Maybe<MerchantWebsiteSection>;
  refund?: Maybe<MerchantWebsiteSection>;
  shipping?: Maybe<MerchantWebsiteSection>;
  status?: Maybe<MerchantWebsiteApprovalStatusEnum>;
  termsAndConditions?: Maybe<MerchantWebsiteSection>;
};

export enum MerchantWebsiteActionEnum {
  DELETE = 'DELETE',
  DOWNLOAD = 'DOWNLOAD',
  PUBLISH = 'PUBLISH',
  SUBMIT = 'SUBMIT',
  UPLOAD = 'UPLOAD',
}

export type MerchantWebsiteAdditionalData = {
  __typename?: 'MerchantWebsiteAdditionalData';
  contactEmail?: Maybe<Scalars['EmailAddress']>;
  contactSupportNumber?: Maybe<Scalars['String']>;
  refundProcessPeriod?: Maybe<Scalars['String']>;
  refundRequestPeriod?: Maybe<Scalars['String']>;
  shippingPeriod?: Maybe<Scalars['String']>;
};

export type MerchantWebsiteAdditionalDataInput = {
  contactEmail?: InputMaybe<Scalars['EmailAddress']>;
  contactSupportNumber?: InputMaybe<Scalars['String']>;
  refundProcessPeriod?: InputMaybe<Scalars['String']>;
  refundRequestPeriod?: InputMaybe<Scalars['String']>;
  shippingPeriod?: InputMaybe<Scalars['String']>;
};

export type MerchantWebsiteApplicationDetail = {
  __typename?: 'MerchantWebsiteApplicationDetail';
  documentId?: Maybe<Scalars['ID']>;
  host: Scalars['URL'];
  signedUrl?: Maybe<Scalars['String']>;
  url?: Maybe<Scalars['URL']>;
};

export type MerchantWebsiteApplicationInput = {
  host: Scalars['URL'];
  url?: InputMaybe<Scalars['URL']>;
};

export enum MerchantWebsiteApprovalStatusEnum {
  APPROVED = 'APPROVED',
  NEEDS_CLARIFICATION = 'NEEDS_CLARIFICATION',
  REJECTED = 'REJECTED',
  SUBMITTED = 'SUBMITTED',
  UNDER_REVIEW = 'UNDER_REVIEW',
}

export type MerchantWebsiteDetailsFailureResponse = {
  __typename?: 'MerchantWebsiteDetailsFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantWebsiteDetailsResponse = {
  __typename?: 'MerchantWebsiteDetailsResponse';
  code: Scalars['PositiveInt'];
  merchantWebsite: MerchantWebsite;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum MerchantWebsiteDetailsSubmitEnum {
  SUBMIT = 'SUBMIT',
}

export type MerchantWebsiteDocumentDeleteFailureResponse = {
  __typename?: 'MerchantWebsiteDocumentDeleteFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantWebsiteDocumentDeleteResponse =
  | MerchantWebsiteDocumentDeleteFailureResponse
  | MerchantWebsiteDocumentDeleteSuccessResponse;

export type MerchantWebsiteDocumentDeleteSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantWebsiteDocumentDeleteSuccessResponse';
  code: Scalars['PositiveInt'];
  merchantWebsite: MerchantWebsite;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantWebsiteDocumentUploadFailureResponse = {
  __typename?: 'MerchantWebsiteDocumentUploadFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantWebsiteDocumentUploadResponse =
  | MerchantWebsiteDocumentUploadFailureResponse
  | MerchantWebsiteDocumentUploadSuccessResponse;

export type MerchantWebsiteDocumentUploadSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantWebsiteDocumentUploadSuccessResponse';
  code: Scalars['PositiveInt'];
  merchantWebsite: MerchantWebsite;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum MerchantWebsitePlatformUrlsEnum {
  APP_STORE = 'APP_STORE',
  PLAY_STORE = 'PLAY_STORE',
  WEBSITE = 'WEBSITE',
}

export type MerchantWebsitePublishFailureResponse = {
  __typename?: 'MerchantWebsitePublishFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantWebsitePublishResponse =
  | MerchantWebsitePublishFailureResponse
  | MerchantWebsitePublishSuccessResponse;

export type MerchantWebsitePublishSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantWebsitePublishSuccessResponse';
  code: Scalars['PositiveInt'];
  merchantWebsite: MerchantWebsite;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantWebsiteSection = {
  __typename?: 'MerchantWebsiteSection';
  appStore?: Maybe<Array<Maybe<MerchantWebsiteApplicationDetail>>>;
  playStore?: Maybe<Array<Maybe<MerchantWebsiteApplicationDetail>>>;
  publishedUrl?: Maybe<Scalars['URL']>;
  updatedAt?: Maybe<Scalars['DateTime']>;
  website?: Maybe<Array<Maybe<MerchantWebsiteApplicationDetail>>>;
  websiteApprovalStatus?: Maybe<MerchantWebsiteApprovalStatusEnum>;
  websiteSectionStatus?: Maybe<MerchantWebsiteSectionStatusEnum>;
};

export enum MerchantWebsiteSectionEnum {
  CONTACT_US = 'CONTACT_US',
  PRIVACY = 'PRIVACY',
  REFUND = 'REFUND',
  SHIPPING = 'SHIPPING',
  TERMS_AND_CONDITIONS = 'TERMS_AND_CONDITIONS',
}

export type MerchantWebsiteSectionInput = {
  status?: InputMaybe<MerchantWebsiteDetailsSubmitEnum>;
  website?: InputMaybe<MerchantWebsiteApplicationInput>;
  websiteSectionStatus?: InputMaybe<MerchantWebsiteSectionStatusEnum>;
};

export enum MerchantWebsiteSectionStatusEnum {
  LIVE_WEBSITE = 'LIVE_WEBSITE',
  LIVE_WEBSITE_WITH_PARTIAL_DETAILS = 'LIVE_WEBSITE_WITH_PARTIAL_DETAILS',
  NO_WEBSITE = 'NO_WEBSITE',
}

export type MerchantWebsiteTermsAndConditions = {
  __typename?: 'MerchantWebsiteTermsAndConditions';
  deliverableType: Scalars['String'];
  link: Scalars['String'];
  refundProcessPeriod: Scalars['String'];
  refundRequestPeriod: Scalars['String'];
  shippingPeriod?: Maybe<Scalars['String']>;
  supportEmail?: Maybe<Scalars['EmailAddress']>;
  warrantyPeriod?: Maybe<Scalars['String']>;
};

export type MerchantWebsitesResponse =
  | MerchantWebsiteDetailsFailureResponse
  | MerchantWebsiteDetailsResponse;

export type MerchantWorkflowClarificationSubmitFailureResponse = {
  __typename?: 'MerchantWorkflowClarificationSubmitFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type MerchantWorkflowClarificationSubmitResponse =
  | MerchantWorkflowClarificationSubmitFailureResponse
  | MerchantWorkflowClarificationSubmitSuccessResponse;

export type MerchantWorkflowClarificationSubmitSuccessResponse = MutationResponseInterface & {
  __typename?: 'MerchantWorkflowClarificationSubmitSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type Money = {
  __typename?: 'Money';
  currency: Currency;
  value: Scalars['BigInt'];
};

export type MoneyInput = {
  currency: CurrencyInput;
  value: Scalars['PositiveInt'];
};

export type Mutation = {
  __typename?: 'Mutation';
  aadhaarCaptchaVerify: AadhaarCaptchaVerifyResponse;
  aadhaarDigilockerOtp: AadhaarDigilockerOtpResponse;
  aadhaarDigilockerRedirectionUrl: AadhaarDigilockerRedirectionUrlResponse;
  aadhaarDigilockerRedirectionUrlVerify: AadhaarDigilockerRedirectionUrlVerifyResponse;
  aadhaarOtpVerify: AadhaarOtpVerifyResponse;
  aadharDigilockerOtpVerify: AadhaarDigilockerOtpVerifyResponse;
  /** To send verification otp for an unverified email or  mobile */
  accountVerificationOtp: AccountVerificationOtpResponse;
  /** To resend verification otp for an unverified email or  mobile */
  accountVerificationOtpResend: AccountVerificationOtpResendResponse;
  /** Mutation to verify otp for unverified user */
  accountVerify: Auth;
  approveIciciPayout: ApproveIciciPayoutResponse;
  approvePayout: ApprovePayoutResponse;
  approvePayoutBatch: ApprovePayoutBatchResponse;
  couponApply: CouponApplyResponse;
  couponValidate: CouponValidateResponse;
  deregisterFCMToken: DeregisterFcmTokenResponse;
  loginEmail: Auth;
  /** Mutation to verify otp for unverified user email */
  loginEmailVerify: Auth;
  loginOAuth: Auth;
  /** To send OTP on mobile, will also tell whether the number is unverified */
  loginOtp: LoginOtpResponse;
  /** To resend otp on mobile */
  loginOtpResend: LoginOtpResendResponse;
  /** To verify the mobile number otp, can return multiple error codes for failure cases */
  loginOtpVerify: Auth;
  loginTwoFactor: Auth;
  /** For 2FA flow when user logs in via phone number and otp */
  loginTwoFactorPassword: Auth;
  merchantActivationDetailsUpdate: MerchantActivationResponse;
  merchantActivationDocumentDelete: MerchantActivationResponse;
  merchantActivationDocumentUpload: MerchantActivationResponse;
  /** @deprecated Use merchantApiKeysCreate instead */
  merchantApiKeyCreate: MerchantApiKeyCreateResponse;
  merchantApiKeyRegenerate: MerchantApiKeyRegenerateResponse;
  merchantApiKeysCreate: MerchantApiKeysCreateResponse;
  merchantBankAccountDocumentUpload: MerchantBankAccountDocumentUploadResponse;
  /** Merchant bank account details update mutation */
  merchantBankAccountUpdate: MerchantBankAccountUpdateResponse;
  merchantBusinessAppDetailsUpdate: MerchantBusinessAppDetailsResponse;
  merchantBusinessWebsiteDetailsUpdate: MerchantBusinessWebsiteDetailsResponse;
  merchantClarificationDetailsSubmit: MerchantClarificationDetailsSubmitResponse;
  merchantClarificationDetailsUpdate: MerchantClarificationDetailsUpdateResponse;
  merchantConfigUpdate: MerchantConfigUpdateResponse;
  merchantConfigurationUpdate: MerchantConfigurationUpdateResponse;
  merchantContactCreate: MerchantContactCreateResponse;
  merchantContactEmailOtpSend: MerchantContactEmailOtpSendResponse;
  merchantContactFundAccountCreate: MerchantContactFundAccountCreateResponse;
  merchantContactTypeCreate: MerchantContactTypeCreateResponse;
  merchantContactUpdate: MerchantContactUpdateResponse;
  merchantDocumentUpload: MerchantDocumentUploadResponse;
  merchantEmailUpdate: MerchantEmailUpdateResponse;
  /** Self serve gstin update mutation */
  merchantGstinUpdate: MerchantGstinUpdateResponse;
  merchantKYCPartnerAccessUpdate: MerchantKycPartnerAccessStatusUpdateResponse;
  merchantOnboardingQuestionDetailsUpdate: MerchantOnboardingQuestionDetailsUpdateResponse;
  merchantPaymentHandleCreate: MerchantPaymentHandleCreateResponse;
  /** To fetch encrypted amount corresponding to the optional amount passed for ph */
  merchantPaymentHandleEncryptedAmount: MerchantPaymentHandleEncryptedAmountResponse;
  merchantPaymentHandleUpdate: MerchantPaymentHandleUpdateResponse;
  merchantPolicyPublish: MerchantPolicyPublishResponse;
  merchantStoreConsents: MerchantConsentsResponse;
  merchantSwitch: MerchantSwitchResponse;
  merchantSwitchOAuth: MerchantSwitchResponse;
  merchantWebsiteDetailsUpdate: MerchantWebsite;
  merchantWebsiteDocumentDelete: MerchantWebsiteDocumentDeleteResponse;
  merchantWebsiteDocumentUpload: MerchantWebsiteDocumentUploadResponse;
  merchantWebsitePublish: MerchantWebsitePublishResponse;
  merchantWorkflowClarificationSubmit: MerchantWorkflowClarificationSubmitResponse;
  notificationEmailUpdate: NotificationEmailUpdateResponse;
  /** @deprecated Use whatsappNotificationToggle instead */
  notificationWhatsAppOptIn: NotificationWhatsAppOptIn;
  /** To generate Oauth Tokens providing otp, can return multiple error codes for failure cases */
  oAuthTokenAppleWatch: OauthTokenAppleWatchResponse;
  oAuthTokenAppleWatchOtp: OauthTokenAppleWatchOtp;
  onboardingPaymentOrderCreate: OnboardingPaymentOrderCreateResponse;
  onboardingPaymentOrderVerify: OnboardingPaymentOrderVerifyResponse;
  orderCreate: OrderCreateResponse;
  paymentCapture: PaymentCaptureResponse;
  paymentLinkCancel: PaymentLinkCancelResponse;
  paymentLinkCreate: PaymentLinkCreateResponse;
  paymentLinkNotify: PaymentLinkNotifyResponse;
  paymentRefund: PaymentRefundResponse;
  paymentsNewLaunchProductViewUpdate: PaymentsNewLaunchProductViewUpdate;
  paymentsProductFtuxUpdate: PaymentsProductFtuxUpdateResponse;
  payoutApproveBulk: PayoutApproveBulkResponse;
  payoutCompositeCreate: PayoutCompositeCreateResponse;
  payoutCreate: PayoutCreateResponse;
  payoutCreateIcici: PayoutCreateIciciResponse;
  payoutLinkCreate: PayoutLinkCreateResponse;
  payoutPurposeCreate: PayoutPurposeCreateResponse;
  payoutRejectBulk: PayoutRejectBulkResponse;
  pointOfSalePaymentCreate: PointOfSalePaymentCreateResponse;
  pointOfSalePaymentUpdate: PointOfSalePaymentUpdateResponse;
  qrCodeCreate: QrCodeCreateResponse;
  refreshAccessToken: RefreshAccessToken;
  registerBusiness: RegisterBusinessResponse;
  registerEmail: RegisterEmail;
  registerEmailVerify: RegisterEmailVerifyResponse;
  registerFCMToken: RegisterFcmTokenResponse;
  /** Send or resend otp to phone number provided during sign up */
  registerMerchant: RegisterMerchantResponse;
  /** Verify OTP sent to phone number during sign up */
  registerMobileVerify: RegisterMobileVerifyResponse;
  registerOAuth: RegisterOAuth;
  rejectPayout: RejectPayoutResponse;
  rejectPayoutBatch: RejectPayoutBatchResponse;
  resendEmailOtp: ResendEmailOtp;
  resendTwoFactorLoginOtp: ResendTwoFactorLoginOtpResponse;
  resetPasswordEmail: ResetPasswordEmail;
  sendApprovePayoutBatchOtp: SendApprovePayoutBatchOtp;
  sendApprovePayoutOtp: SendApprovePayoutOtp;
  sendCreatePayoutLinkOtp: SendCreatePayoutLinkOtp;
  sendCreatePayoutOtp: SendCreatePayoutOtp;
  sendIciciPayoutOtp: SendIciciPayoutOtpResponse;
  sendPayoutApproveBulkOtp: SendPayoutApproveBulkOtp;
  sendPayoutCompositeOtp: SendPayoutCompositeOtp;
  /** handle sms notification toggle */
  smsNotificationToggle: SmsNotificationToggle;
  /** Mutation to add contact and send otp to the given unverified contact for verification */
  twoFactorAddMobileOtp: TwoFactorAddMobileOtpResponse;
  /** Mutation to verify mobile otp and add the mobile in user account */
  twoFactorAddMobileOtpVerify: TwoFactorAddMobileOtpVerifyResponse;
  /** Mutation for updating user's twoFactorAuth flag to enabled or disabled */
  twoFactorAuthUpdate: TwoFactorAuthUpdateResponse;
  /** Mutation to verify Email otp for Two Factor Auth */
  twoFactorEmailOtpVerify: TwoFactorEmailOtpVerifyResponse;
  /** Mutation to send Email otp for Two Factor Auth */
  twoFactorOtp: TwoFactorOtpResponse;
  /** Mutation to add/create a password for 2FA */
  twoFactorPasswordCreate: TwoFactorPasswordCreateResponse;
  /** Mutation to verify unverified mobile otp for Two Factor Auth */
  twoFactorUnverifiedMobileVerify: TwoFactorUnverifiedMobileVerifyResponse;
  updateMerchantConsent?: Maybe<UpdateMerchantConsentResponse>;
  userContactDetailsUpdate: RegisterBusinessResponse;
  userDeviceAnalyticsUpdate?: Maybe<UserDeviceAnalyticsResponse>;
  userLogout: UserLogout;
  userLogoutOAuth: UserLogout;
  userOtp: UserOtpResponse;
  userOtpVerify: UserOtpVerifyResponse;
  vendorPaymentCancel: VendorPaymentCancelResponse;
  vendorPaymentPayoutCreate: VendorPaymentPayoutCreateResponse;
  /** To handle whatsapp notification toggle */
  whatsappNotificationToggle: WhatsappNotificationToggle;
};

export type MutationAadhaarCaptchaVerifyArgs = {
  aadhaarNumber: Scalars['String'];
  captcha: Scalars['String'];
};

export type MutationAadhaarDigilockerOtpArgs = {
  aadhaarNumber: Scalars['String'];
};

export type MutationAadhaarDigilockerRedirectionUrlArgs = {
  redirectUrl: Scalars['String'];
  verificationType: DigilockerVerificationTypeEnum;
};

export type MutationAadhaarDigilockerRedirectionUrlVerifyArgs = {
  verificationType: DigilockerVerificationTypeEnum;
};

export type MutationAadhaarOtpVerifyArgs = {
  captcha: Scalars['String'];
  filePassword: Scalars['String'];
  otp: Scalars['String'];
};

export type MutationAadharDigilockerOtpVerifyArgs = {
  aadhaarNumber: Scalars['String'];
  otp: Scalars['String'];
  requestId: Scalars['String'];
};

export type MutationAccountVerificationOtpArgs = {
  email?: InputMaybe<Scalars['EmailAddress']>;
  password: Scalars['String'];
  phone?: InputMaybe<PhoneInput>;
};

export type MutationAccountVerificationOtpResendArgs = {
  email?: InputMaybe<Scalars['EmailAddress']>;
  password: Scalars['String'];
  phone?: InputMaybe<PhoneInput>;
  token: Scalars['String'];
};

export type MutationAccountVerifyArgs = {
  captcha?: InputMaybe<Scalars['String']>;
  email?: InputMaybe<Scalars['EmailAddress']>;
  otp: Scalars['String'];
  phone?: InputMaybe<PhoneInput>;
  token: Scalars['String'];
};

export type MutationApproveIciciPayoutArgs = {
  id: Scalars['ID'];
  otp: Scalars['String'];
};

export type MutationApprovePayoutArgs = {
  comment?: InputMaybe<Scalars['String']>;
  id: Scalars['ID'];
  otp: Scalars['String'];
  queueOnLowBalance: Scalars['Int'];
  token: Scalars['String'];
};

export type MutationApprovePayoutBatchArgs = {
  batchIds: Array<Scalars['ID']>;
  comment?: InputMaybe<Scalars['String']>;
  otp: Scalars['String'];
  token: Scalars['String'];
};

export type MutationCouponValidateArgs = {
  code: Scalars['ID'];
};

export type MutationDeregisterFcmTokenArgs = {
  productType: ProductTypeEnum;
  tokenIdentifier: Scalars['String'];
};

export type MutationLoginEmailArgs = {
  captcha?: InputMaybe<Scalars['String']>;
  captchaMode?: InputMaybe<Scalars['String']>;
  email: Scalars['EmailAddress'];
  password: Scalars['String'];
};

export type MutationLoginEmailVerifyArgs = {
  otp: Scalars['String'];
  token: Scalars['String'];
};

export type MutationLoginOAuthArgs = {
  email: Scalars['EmailAddress'];
  idToken: Scalars['String'];
  platform: ClientPlatformEnum;
  provider: OAuthProviderEnum;
};

export type MutationLoginOtpArgs = {
  mockSend?: InputMaybe<Scalars['Boolean']>;
  phone: PhoneInput;
};

export type MutationLoginOtpResendArgs = {
  mockSend?: InputMaybe<Scalars['Boolean']>;
  phone: PhoneInput;
  token?: InputMaybe<Scalars['String']>;
};

export type MutationLoginOtpVerifyArgs = {
  captcha?: InputMaybe<Scalars['String']>;
  captchaMode?: InputMaybe<CaptchaModeEnum>;
  mockSend?: InputMaybe<Scalars['Boolean']>;
  otp: Scalars['String'];
  partnerId?: InputMaybe<Scalars['String']>;
  phone: PhoneInput;
  token: Scalars['String'];
};

export type MutationLoginTwoFactorArgs = {
  code: Scalars['String'];
  oAuthToken?: InputMaybe<Scalars['String']>;
};

export type MutationLoginTwoFactorPasswordArgs = {
  oAuthToken?: InputMaybe<Scalars['String']>;
  password: Scalars['String'];
};

export type MutationMerchantActivationDetailsUpdateArgs = {
  activationDataSubmitted?: InputMaybe<Scalars['Boolean']>;
  activationFormMilestone?: InputMaybe<MerchantActivationMilestoneEnum>;
  bank?: InputMaybe<MerchantBankInput>;
  business?: InputMaybe<MerchantBusinessInput>;
  consent?: InputMaybe<MerchantConsentInput>;
  contactPerson?: InputMaybe<MerchantContactPersonInput>;
  document?: InputMaybe<MerchantDocumentInput>;
  partnerId?: InputMaybe<Scalars['String']>;
  source?: InputMaybe<Scalars['String']>;
  stakeholder?: InputMaybe<MerchantStakeholderInput>;
};

export type MutationMerchantActivationDocumentDeleteArgs = {
  documentId: Scalars['ID'];
};

export type MutationMerchantActivationDocumentUploadArgs = {
  file: Scalars['Upload'];
  name: Scalars['String'];
};

export type MutationMerchantApiKeyRegenerateArgs = {
  apiKeyRegenerationDelayType: ApiKeyRegenerationDelayTypeEnum;
  oldApiKey: Scalars['String'];
};

export type MutationMerchantBankAccountDocumentUploadArgs = {
  document: Scalars['Upload'];
};

export type MutationMerchantBankAccountUpdateArgs = {
  accountNumber: Scalars['String'];
  addressProofDocument?: InputMaybe<Scalars['Upload']>;
  beneficiaryName: Scalars['String'];
  ifscCode: Scalars['String'];
  isSyncOnly?: InputMaybe<Scalars['Boolean']>;
};

export type MutationMerchantBusinessAppDetailsUpdateArgs = {
  businessApp: MerchantBusinessAppInput;
};

export type MutationMerchantBusinessWebsiteDetailsUpdateArgs = {
  businessWebsite: MerchantBusinessWebsiteInput;
};

export type MutationMerchantClarificationDetailsSubmitArgs = {
  submitField?: InputMaybe<Scalars['Boolean']>;
};

export type MutationMerchantClarificationDetailsUpdateArgs = {
  comment?: InputMaybe<Scalars['String']>;
  commentType?: InputMaybe<MerchantCommentTypeEnum>;
  fieldDetails?: InputMaybe<FieldDetailsInput>;
  fieldName: Scalars['String'];
  submitField?: InputMaybe<Scalars['Boolean']>;
};

export type MutationMerchantConfigUpdateArgs = {
  couponPopupCount?: InputMaybe<Scalars['PositiveInt']>;
  namespace: Scalars['String'];
  referralSuccessPopupCount?: InputMaybe<Scalars['NonNegativeInt']>;
};

export type MutationMerchantConfigurationUpdateArgs = {
  onboardingConfiguration?: InputMaybe<MerchantOnboardingConfigurationInput>;
};

export type MutationMerchantContactCreateArgs = {
  email?: InputMaybe<Scalars['EmailAddress']>;
  name: Scalars['String'];
  notes?: InputMaybe<Scalars['JSONObject']>;
  phone?: InputMaybe<PhoneInput>;
  reference?: InputMaybe<Scalars['String']>;
  type?: InputMaybe<Scalars['String']>;
};

export type MutationMerchantContactEmailOtpSendArgs = {
  email: Scalars['EmailAddress'];
  otpVerificationToken?: InputMaybe<Scalars['String']>;
};

export type MutationMerchantContactFundAccountCreateArgs = {
  bankAccount?: InputMaybe<MerchantContactFundAccountBankAccountInput>;
  contactId: Scalars['ID'];
  type: MerchantContactFundAccountTypeEnum;
  vpa?: InputMaybe<MerchantContactFundAccountVpaInput>;
};

export type MutationMerchantContactTypeCreateArgs = {
  type: Scalars['String'];
};

export type MutationMerchantContactUpdateArgs = {
  active?: InputMaybe<Scalars['Boolean']>;
  email?: InputMaybe<Scalars['EmailAddress']>;
  id: Scalars['ID'];
  name?: InputMaybe<Scalars['String']>;
  notes?: InputMaybe<Scalars['JSON']>;
  phone?: InputMaybe<PhoneInput>;
  reference?: InputMaybe<Scalars['String']>;
  type?: InputMaybe<Scalars['String']>;
};

export type MutationMerchantDocumentUploadArgs = {
  document: Scalars['Upload'];
  purpose: MerchantDocumentUploadPurposeEnum;
};

export type MutationMerchantEmailUpdateArgs = {
  shouldUpdateContactEmail: Scalars['Boolean'];
  updatedEmail: Scalars['EmailAddress'];
};

export type MutationMerchantGstinUpdateArgs = {
  gstin: Scalars['String'];
  gstinCertificate: Scalars['Upload'];
};

export type MutationMerchantKycPartnerAccessUpdateArgs = {
  referralCode: Scalars['String'];
  status: MerchantKycPartnerAccessInputTypeEnum;
};

export type MutationMerchantOnboardingQuestionDetailsUpdateArgs = {
  questionDetails: Array<MerchantOnboardingQuestionDetailInput>;
};

export type MutationMerchantPaymentHandleEncryptedAmountArgs = {
  amount: MoneyInput;
};

export type MutationMerchantPaymentHandleUpdateArgs = {
  paymentHandleSlug: Scalars['String'];
  paymentPageId?: InputMaybe<Scalars['String']>;
};

export type MutationMerchantPolicyPublishArgs = {
  action: MerchantWebsiteActionEnum;
  section: Array<MerchantWebsiteSectionEnum>;
  submit?: InputMaybe<Scalars['Boolean']>;
};

export type MutationMerchantStoreConsentsArgs = {
  consents: Array<MerchantConsentPayload>;
  event: MerchantConsentEvent;
};

export type MutationMerchantSwitchArgs = {
  id: Scalars['ID'];
};

export type MutationMerchantSwitchOAuthArgs = {
  accessToken: Scalars['String'];
  clientId: Scalars['String'];
  id: Scalars['ID'];
};

export type MutationMerchantWebsiteDetailsUpdateArgs = {
  additionalData?: InputMaybe<MerchantWebsiteAdditionalDataInput>;
  contactUs?: InputMaybe<MerchantWebsiteSectionInput>;
  privacy?: InputMaybe<MerchantWebsiteSectionInput>;
  refund?: InputMaybe<MerchantWebsiteSectionInput>;
  shipping?: InputMaybe<MerchantWebsiteSectionInput>;
  status?: InputMaybe<MerchantWebsiteDetailsSubmitEnum>;
  termsAndConditions?: InputMaybe<MerchantWebsiteSectionInput>;
};

export type MutationMerchantWebsiteDocumentDeleteArgs = {
  platform: MerchantWebsitePlatformUrlsEnum;
  section: MerchantWebsiteSectionEnum;
};

export type MutationMerchantWebsiteDocumentUploadArgs = {
  platform: MerchantWebsitePlatformUrlsEnum;
  section: MerchantWebsiteSectionEnum;
  websiteDocument: Scalars['Upload'];
};

export type MutationMerchantWebsitePublishArgs = {
  action: MerchantWebsiteActionEnum;
  consentUrl?: InputMaybe<Scalars['URL']>;
  hasMerchantConsent: Scalars['Boolean'];
  section: MerchantWebsiteSectionEnum;
};

export type MutationMerchantWorkflowClarificationSubmitArgs = {
  clarificationReason: Scalars['String'];
  documentIds: Array<InputMaybe<Scalars['ID']>>;
  workflow: MerchantSelfServeWorkflowEnum;
};

export type MutationNotificationEmailUpdateArgs = {
  transactionReportEmail: Array<Scalars['EmailAddress']>;
};

export type MutationNotificationWhatsAppOptInArgs = {
  source: Scalars['String'];
};

export type MutationOAuthTokenAppleWatchArgs = {
  otp: Scalars['String'];
  token: Scalars['String'];
};

export type MutationOnboardingPaymentOrderCreateArgs = {
  createOrder: Scalars['Boolean'];
};

export type MutationOnboardingPaymentOrderVerifyArgs = {
  orderId: Scalars['String'];
  paymentId: Scalars['String'];
  signature: Scalars['String'];
};

export type MutationOrderCreateArgs = {
  amount: MoneyInput;
  notes?: InputMaybe<Scalars['JSONObject']>;
  receipt?: InputMaybe<Scalars['String']>;
};

export type MutationPaymentCaptureArgs = {
  amount: MoneyInput;
  id: Scalars['ID'];
};

export type MutationPaymentLinkCancelArgs = {
  id: Scalars['ID'];
};

export type MutationPaymentLinkCreateArgs = {
  amount: MoneyInput;
  autoReminder?: InputMaybe<Scalars['Boolean']>;
  customer?: InputMaybe<CustomerInput>;
  description?: InputMaybe<Scalars['String']>;
  expireBy?: InputMaybe<Scalars['DateTime']>;
  firstMinimumPartialAmount?: InputMaybe<MoneyInput>;
  isPartiallyPayable?: InputMaybe<Scalars['Boolean']>;
  notes?: InputMaybe<Scalars['JSONObject']>;
  notifyBy?: InputMaybe<PaymentLinkNotifyByInput>;
  referenceId?: InputMaybe<Scalars['String']>;
};

export type MutationPaymentLinkNotifyArgs = {
  id: Scalars['ID'];
  medium: PaymentLinkNotifyMedium;
};

export type MutationPaymentRefundArgs = {
  amount: MoneyInput;
  comment?: InputMaybe<Scalars['String']>;
  id: Scalars['ID'];
  speed?: InputMaybe<PaymentRefundSpeedRequestedEnum>;
};

export type MutationPaymentsProductFtuxUpdateArgs = {
  isFtuxComplete: Scalars['Boolean'];
  isNewLaunch?: InputMaybe<Scalars['Boolean']>;
  product: Scalars['String'];
};

export type MutationPayoutApproveBulkArgs = {
  comment?: InputMaybe<Scalars['String']>;
  ids: Array<Scalars['ID']>;
  otp: Scalars['String'];
  queueOnLowBalance: Scalars['Boolean'];
  token: Scalars['String'];
};

export type MutationPayoutCompositeCreateArgs = {
  amount: MoneyInput;
  bankingAccountNumber: Scalars['String'];
  fundAccountType: MerchantContactFundAccountTypeEnum;
  merchantContact: PayoutCompositeMerchantContactInput;
  mode: PayoutModeEnum;
  narration?: InputMaybe<Scalars['String']>;
  notes?: InputMaybe<Scalars['JSONObject']>;
  otp: Scalars['String'];
  purpose: Scalars['String'];
  queueOnLowBalance: Scalars['Boolean'];
  token: Scalars['String'];
  vpa: MerchantContactFundAccountVpaInput;
};

export type MutationPayoutCreateArgs = {
  amount: MoneyInput;
  bankingAccountNumber: Scalars['String'];
  fundAccountId: Scalars['String'];
  mode: PayoutModeEnum;
  narration?: InputMaybe<Scalars['String']>;
  notes?: InputMaybe<Scalars['JSONObject']>;
  otp: Scalars['String'];
  purpose: Scalars['String'];
  queueOnLowBalance: Scalars['Int'];
  token: Scalars['String'];
};

export type MutationPayoutCreateIciciArgs = {
  amount: MoneyInput;
  bankingAccountNumber: Scalars['String'];
  fundAccountId: Scalars['String'];
  mode: PayoutModeEnum;
  narration?: InputMaybe<Scalars['String']>;
  notes?: InputMaybe<Scalars['JSONObject']>;
  purpose: Scalars['String'];
  queueOnLowBalance: Scalars['Boolean'];
};

export type MutationPayoutLinkCreateArgs = {
  amount: MoneyInput;
  bankingAccountNumber: Scalars['String'];
  description: Scalars['String'];
  merchantContactEmail?: InputMaybe<Scalars['EmailAddress']>;
  merchantContactId: Scalars['String'];
  merchantContactPhone?: InputMaybe<PhoneInput>;
  notes?: InputMaybe<Scalars['JSONObject']>;
  otp: Scalars['String'];
  purpose: Scalars['String'];
  referenceId?: InputMaybe<Scalars['String']>;
  sendVia: PayoutLinkSendVia;
  token: Scalars['String'];
};

export type MutationPayoutPurposeCreateArgs = {
  label: Scalars['String'];
  type: PayoutPurposeTypeEnum;
};

export type MutationPayoutRejectBulkArgs = {
  comment?: InputMaybe<Scalars['String']>;
  ids: Array<Scalars['ID']>;
};

export type MutationPointOfSalePaymentCreateArgs = {
  amount: MoneyInput;
  application: PaymentApplicationEnum;
  method: PaymentMethodEnum;
  orderId: Scalars['ID'];
};

export type MutationPointOfSalePaymentUpdateArgs = {
  amount: MoneyInput;
  transaction: PointOfSalePaymentTransactionInput;
  url: Scalars['URL'];
};

export type MutationQrCodeCreateArgs = {
  amount?: InputMaybe<MoneyInput>;
  description?: InputMaybe<Scalars['String']>;
  isFixedAmount?: InputMaybe<Scalars['Boolean']>;
  name?: InputMaybe<Scalars['String']>;
  type: QrCodeTypeEnum;
  usage: QrCodeUsageEnum;
};

export type MutationRefreshAccessTokenArgs = {
  clientId: Scalars['String'];
  merchantId: Scalars['String'];
  refreshToken: Scalars['String'];
};

export type MutationRegisterBusinessArgs = {
  businessType?: InputMaybe<MerchantBusinessTypeEnum>;
  contact?: InputMaybe<PhoneInput>;
  couponCode?: InputMaybe<Scalars['String']>;
  name: Scalars['String'];
  partnerId?: InputMaybe<Scalars['String']>;
  referralCode?: InputMaybe<Scalars['String']>;
  transactionVolume?: InputMaybe<MerchantMonthlyRevenueEnum>;
};

export type MutationRegisterEmailArgs = {
  captcha?: InputMaybe<Scalars['String']>;
  captchaMode?: InputMaybe<CaptchaModeEnum>;
  confirmPassword: Scalars['String'];
  email: Scalars['EmailAddress'];
  partnerIntent?: InputMaybe<Scalars['Boolean']>;
  password: Scalars['String'];
  signupCampaign?: InputMaybe<UserSignupCampaignEnum>;
  verificationMethod: RegisterEmailVerificationMethodEnum;
};

export type MutationRegisterEmailVerifyArgs = {
  otp: Scalars['String'];
  token: Scalars['String'];
};

export type MutationRegisterFcmTokenArgs = {
  fcmToken: Scalars['String'];
  platform: PlatformEnum;
  productType: ProductTypeEnum;
  tokenIdentifier: Scalars['String'];
};

export type MutationRegisterMerchantArgs = {
  contact: PhoneInput;
  mockSend?: InputMaybe<Scalars['Boolean']>;
  token?: InputMaybe<Scalars['String']>;
};

export type MutationRegisterMobileVerifyArgs = {
  captcha: Scalars['String'];
  captchaMode?: InputMaybe<CaptchaModeEnum>;
  contact: PhoneInput;
  countryCode?: InputMaybe<Scalars['String']>;
  mockSend?: InputMaybe<Scalars['Boolean']>;
  otp: Scalars['String'];
  partnerReferralCode?: InputMaybe<Scalars['String']>;
  referralCode?: InputMaybe<Scalars['String']>;
  signupCampaign?: InputMaybe<UserSignupCampaignEnum>;
  source?: InputMaybe<Scalars['String']>;
  token: Scalars['String'];
  utmCampaign?: InputMaybe<Scalars['String']>;
  utmMedium?: InputMaybe<Scalars['String']>;
  utmSource?: InputMaybe<Scalars['String']>;
};

export type MutationRegisterOAuthArgs = {
  email: Scalars['EmailAddress'];
  idToken: Scalars['String'];
  partnerIntent?: InputMaybe<Scalars['Boolean']>;
  platform: ClientPlatformEnum;
  provider: OAuthProviderEnum;
};

export type MutationRejectPayoutArgs = {
  comment?: InputMaybe<Scalars['String']>;
  id: Scalars['ID'];
};

export type MutationRejectPayoutBatchArgs = {
  batchIds: Array<Scalars['ID']>;
  comment?: InputMaybe<Scalars['String']>;
};

export type MutationResendEmailOtpArgs = {
  token: Scalars['String'];
};

export type MutationResendTwoFactorLoginOtpArgs = {
  oAuthToken?: InputMaybe<Scalars['String']>;
};

export type MutationResetPasswordEmailArgs = {
  email: Scalars['EmailAddress'];
};

export type MutationSendApprovePayoutBatchOtpArgs = {
  bankingAccountNumber: Scalars['String'];
  totalAmount: MoneyInput;
  totalCount: Scalars['PositiveInt'];
};

export type MutationSendApprovePayoutOtpArgs = {
  amount: MoneyInput;
  bankingAccountNumber: Scalars['String'];
  payoutId: Scalars['ID'];
};

export type MutationSendCreatePayoutLinkOtpArgs = {
  amount: MoneyInput;
  bankingAccountNumber: Scalars['String'];
  email?: InputMaybe<Scalars['EmailAddress']>;
  phone?: InputMaybe<PhoneInput>;
  purpose: Scalars['String'];
};

export type MutationSendCreatePayoutOtpArgs = {
  amount: MoneyInput;
  bankingAccountNumber: Scalars['String'];
  fundAccountId: Scalars['String'];
  purpose: Scalars['String'];
};

export type MutationSendIciciPayoutOtpArgs = {
  payoutId: Scalars['ID'];
};

export type MutationSendPayoutApproveBulkOtpArgs = {
  amount: MoneyInput;
  bankingAccountNumber: Scalars['String'];
  count: Scalars['Int'];
};

export type MutationSendPayoutCompositeOtpArgs = {
  amount: MoneyInput;
  bankingAccountNumber: Scalars['String'];
  vpa: Scalars['VPA'];
};

export type MutationSmsNotificationToggleArgs = {
  toggleValue: Scalars['Boolean'];
};

export type MutationTwoFactorAddMobileOtpArgs = {
  phone: PhoneInput;
  token: Scalars['String'];
};

export type MutationTwoFactorAddMobileOtpVerifyArgs = {
  otp: Scalars['String'];
  phone: PhoneInput;
};

export type MutationTwoFactorAuthUpdateArgs = {
  isTwoFactorEnabled: Scalars['Boolean'];
};

export type MutationTwoFactorEmailOtpVerifyArgs = {
  action: TwoFactorActionTypeEnum;
  otp: Scalars['String'];
  token: Scalars['String'];
};

export type MutationTwoFactorOtpArgs = {
  action: TwoFactorActionTypeEnum;
  medium: TwoFactorOtpMediumEnum;
};

export type MutationTwoFactorPasswordCreateArgs = {
  confirmPassword: Scalars['String'];
  password: Scalars['String'];
};

export type MutationTwoFactorUnverifiedMobileVerifyArgs = {
  otp: Scalars['String'];
  token: Scalars['String'];
};

export type MutationUpdateMerchantConsentArgs = {
  partnerId: Scalars['ID'];
};

export type MutationUserContactDetailsUpdateArgs = {
  contact?: InputMaybe<PhoneInput>;
  name: Scalars['String'];
};

export type MutationUserDeviceAnalyticsUpdateArgs = {
  analyticsData: DeviceAnalyticsDataInput;
};

export type MutationUserLogoutOAuthArgs = {
  accessToken: Scalars['String'];
  clientId: Scalars['ID'];
};

export type MutationUserOtpVerifyArgs = {
  otp: Scalars['String'];
};

export type MutationVendorPaymentCancelArgs = {
  comment?: InputMaybe<Scalars['String']>;
  id: Scalars['ID'];
};

export type MutationVendorPaymentPayoutCreateArgs = {
  amount: MoneyInput;
  bankingAccountNumber: Scalars['String'];
  fundAccountId: Scalars['String'];
  mode: PayoutModeEnum;
  narration?: InputMaybe<Scalars['String']>;
  otp: Scalars['String'];
  purpose: Scalars['String'];
  queueOnLowBalance: Scalars['Boolean'];
  token: Scalars['String'];
  vendorPaymentId: Scalars['ID'];
};

export type MutationWhatsappNotificationToggleArgs = {
  source: Scalars['String'];
  toggleValue: Scalars['Boolean'];
};

export type MutationResponseInterface = {
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type NotificationEmailUpdateFailureResponse = MutationResponseInterface & {
  __typename?: 'NotificationEmailUpdateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type NotificationEmailUpdateResponse =
  | NotificationEmailUpdateFailureResponse
  | NotificationEmailUpdateSuccessResponse;

export type NotificationEmailUpdateSuccessResponse = MutationResponseInterface & {
  __typename?: 'NotificationEmailUpdateSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  transactionReportEmail: Array<Maybe<Scalars['EmailAddress']>>;
};

export type NotificationWhatsAppOptIn = MutationResponseInterface & {
  __typename?: 'NotificationWhatsAppOptIn';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum OAuthProviderEnum {
  GOOGLE = 'GOOGLE',
}

export type OauthTokenAppleWatchOtp = MutationResponseInterface & {
  __typename?: 'OauthTokenAppleWatchOtp';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token: Scalars['String'];
};

export type OauthTokenAppleWatchResponse =
  | OauthTokenAppleWatchResponseError
  | OauthTokenAppleWatchResponseSuccess;

export type OauthTokenAppleWatchResponseError = MutationResponseInterface & {
  __typename?: 'OauthTokenAppleWatchResponseError';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type OauthTokenAppleWatchResponseSuccess = {
  __typename?: 'OauthTokenAppleWatchResponseSuccess';
  accessToken: Scalars['String'];
  accountId: Scalars['String'];
  code: Scalars['PositiveInt'];
  expiresIn: Scalars['DateTime'];
  publicToken: Scalars['String'];
  success: Scalars['Boolean'];
  tokenType: Scalars['String'];
};

export type OnboardingPaymentOrderCreateFailureResponse = MutationResponseInterface & {
  __typename?: 'OnboardingPaymentOrderCreateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type OnboardingPaymentOrderCreateResponse =
  | OnboardingPaymentOrderCreateFailureResponse
  | OnboardingPaymentOrderCreateSuccessResponse;

export type OnboardingPaymentOrderCreateSuccessResponse = MutationResponseInterface & {
  __typename?: 'OnboardingPaymentOrderCreateSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  order: Order;
  success: Scalars['Boolean'];
};

export type OnboardingPaymentOrderVerifyResponse = MutationResponseInterface & {
  __typename?: 'OnboardingPaymentOrderVerifyResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type OnboardingWidget = {
  __typename?: 'OnboardingWidget';
  title: Scalars['String'];
  type: PaymentsWidgetTypeEnum;
  variant: WidgetVariantEnum;
};

export type Order = {
  __typename?: 'Order';
  amount: OrderAmount;
  dates: OrderDate;
  id: Scalars['ID'];
  notes?: Maybe<Scalars['JSONObject']>;
  paymentAttempts: Scalars['NonNegativeInt'];
  receipt?: Maybe<Scalars['String']>;
  status: OrderStatusEnum;
};

export type OrderAmount = {
  __typename?: 'OrderAmount';
  due?: Maybe<Money>;
  generated: Money;
  paid?: Maybe<Money>;
};

export type OrderCreateFailureResponse = MutationResponseInterface & {
  __typename?: 'OrderCreateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type OrderCreateResponse = OrderCreateFailureResponse | OrderCreateSuccessResponse;

export type OrderCreateSuccessResponse = MutationResponseInterface & {
  __typename?: 'OrderCreateSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  order: Order;
  success: Scalars['Boolean'];
};

export type OrderDate = {
  __typename?: 'OrderDate';
  createdAt: Scalars['DateTime'];
};

export enum OrderStatusEnum {
  ATTEMPTED = 'ATTEMPTED',
  CREATED = 'CREATED',
  PAID = 'PAID',
}

export type Organisation = {
  __typename?: 'Organisation';
  allowedEmailDomains: Array<Scalars['String']>;
  code: Scalars['String'];
  domain: Scalars['String'];
  email: OrganisationEmail;
  id: Scalars['ID'];
  logo?: Maybe<OrganisationLogo>;
  name: OrganisationName;
};

export type OrganisationEmail = {
  __typename?: 'OrganisationEmail';
  from: Scalars['EmailAddress'];
  to: Scalars['EmailAddress'];
};

export type OrganisationLogo = {
  __typename?: 'OrganisationLogo';
  header?: Maybe<Image>;
  invoice?: Maybe<Image>;
  login?: Maybe<Image>;
};

export type OrganisationName = {
  __typename?: 'OrganisationName';
  display?: Maybe<Scalars['String']>;
  registered?: Maybe<Scalars['String']>;
};

export type OverViewResponseType = {
  __typename?: 'OverViewResponseType';
  count: Scalars['PositiveInt'];
  reason: Scalars['String'];
};

export type PpTrackingSettings = {
  __typename?: 'PPTrackingSettings';
  ppFbEventAddToCartEnabled?: Maybe<Scalars['String']>;
  ppFbEventInitiatePaymentEnabled?: Maybe<Scalars['String']>;
  ppFbEventPaymentCompleteEnabled?: Maybe<Scalars['String']>;
  ppFbPixelTrackingId?: Maybe<Scalars['String']>;
  ppGaPixelTrackingId?: Maybe<Scalars['String']>;
};

export type PageAcquirerData = {
  __typename?: 'PageAcquirerData';
  transactionId?: Maybe<Scalars['String']>;
};

export type PageItem = {
  __typename?: 'PageItem';
  active: Scalars['Boolean'];
  amount?: Maybe<Scalars['Int']>;
  description?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  name: Scalars['String'];
  taxDetails: PageItemTaxDetails;
  type: Scalars['String'];
};

export type PageItemTaxDetails = {
  __typename?: 'PageItemTaxDetails';
  sacCode?: Maybe<Scalars['String']>;
  taxGroupId?: Maybe<Scalars['String']>;
  taxId?: Maybe<Scalars['String']>;
  taxInclusive?: Maybe<Scalars['Boolean']>;
  taxRate?: Maybe<Scalars['String']>;
};

export type PaginationResponseInterface = {
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  total?: Maybe<Scalars['NonNegativeInt']>;
};

export type PartnerConfigFailure = {
  __typename?: 'PartnerConfigFailure';
  message: Scalars['String'];
  success: Scalars['Boolean'];
};

export type PartnerConfigResponse = PartnerConfigFailure | PartnerConfigSuccess;

export type PartnerConfigSuccess = {
  __typename?: 'PartnerConfigSuccess';
  brandColor: Scalars['String'];
  id: Scalars['ID'];
  logoUrl: Scalars['String'];
  name: Scalars['String'];
  textColor: Scalars['String'];
};

export type PartnerWebhookSettings = {
  __typename?: 'PartnerWebhookSettings';
  partnerShiprocket?: Maybe<Scalars['String']>;
};

export type Payment = {
  __typename?: 'Payment';
  amount: PaymentAmount;
  createdAt: Scalars['DateTime'];
  customer?: Maybe<Customer>;
  description?: Maybe<Scalars['String']>;
  error?: Maybe<PaymentError>;
  feeBearer?: Maybe<FeeBearerEnum>;
  id: Scalars['ID'];
  isCaptured: Scalars['Boolean'];
  isInternational: Scalars['Boolean'];
  method?: Maybe<PaymentMethod>;
  notes?: Maybe<Scalars['JSONObject']>;
  refunds?: Maybe<Array<PaymentRefund>>;
  status: PaymentStatusEnum;
};

export type PaymentAggregationSummary = {
  __typename?: 'PaymentAggregationSummary';
  count: Scalars['NonNegativeInt'];
  sum: Scalars['NonNegativeInt'];
};

export type PaymentAmount = {
  __typename?: 'PaymentAmount';
  charged: Money;
  /** converted = charged * exchange_rate */
  converted?: Maybe<Money>;
  fee?: Maybe<Scalars['Float']>;
  tax?: Maybe<Scalars['Float']>;
};

export type PaymentAnalytics = {
  __typename?: 'PaymentAnalytics';
  bank?: Maybe<Scalars['String']>;
  createdAt?: Maybe<Scalars['DateTime']>;
  device?: Maybe<PaymentAnalyticsFilterByDeviceEnum>;
  issuer?: Maybe<Scalars['String']>;
  method?: Maybe<Scalars['String']>;
  network?: Maybe<Scalars['String']>;
  os?: Maybe<PaymentAnalyticsFilterByOsEnum>;
  platform?: Maybe<PaymentAnalyticsFilterBySdkEnum>;
  type?: Maybe<Scalars['String']>;
  value: Scalars['BigInt'];
  wallet?: Maybe<Scalars['String']>;
};

export enum PaymentAnalyticsAggregateByEnum {
  AVERAGE = 'AVERAGE',
  COUNT = 'COUNT',
  GROWTH_RATE = 'GROWTH_RATE',
  OLDEST = 'OLDEST',
  PERCENT = 'PERCENT',
  RECENT = 'RECENT',
  SORTED_SUM = 'SORTED_SUM',
  SUCCESS_RATE = 'SUCCESS_RATE',
  SUM = 'SUM',
}

export type PaymentAnalyticsFilterBy = {
  device?: InputMaybe<Array<PaymentAnalyticsFilterByDeviceEnum>>;
  os?: InputMaybe<Array<PaymentAnalyticsFilterByOsEnum>>;
  payment?: InputMaybe<Array<PaymentAnalyticsFilterByPaymentEnum>>;
  sdk?: InputMaybe<Array<PaymentAnalyticsFilterBySdkEnum>>;
};

export enum PaymentAnalyticsFilterByDeviceEnum {
  DESKTOP = 'DESKTOP',
  MOBILE = 'MOBILE',
  OTHERS = 'OTHERS',
}

export enum PaymentAnalyticsFilterByOsEnum {
  ANDROID = 'ANDROID',
  IOS = 'IOS',
  OTHERS = 'OTHERS',
}

export enum PaymentAnalyticsFilterByPaymentEnum {
  BANK = 'BANK',
  ISSUER = 'ISSUER',
  METHOD = 'METHOD',
  NETWORK = 'NETWORK',
  TYPE = 'TYPE',
  WALLET = 'WALLET',
}

export enum PaymentAnalyticsFilterBySdkEnum {
  APP = 'APP',
  BROWSER = 'BROWSER',
  OTHERS = 'OTHERS',
}

export enum PaymentAnalyticsIntervalEnum {
  DAILY = 'DAILY',
  HOURLY = 'HOURLY',
  MONTHLY = 'MONTHLY',
  WEEKLY = 'WEEKLY',
}

export type PaymentAnalyticsResponse = {
  __typename?: 'PaymentAnalyticsResponse';
  aggregatedBy: PaymentAnalyticsAggregateByEnum;
  analytics?: Maybe<Array<Maybe<PaymentAnalytics>>>;
  interval?: Maybe<PaymentAnalyticsIntervalEnum>;
  updatedAt: Scalars['DateTime'];
};

export type PaymentAnalyticsWidget = {
  __typename?: 'PaymentAnalyticsWidget';
  title: Scalars['String'];
  type: PaymentsWidgetTypeEnum;
  variant: WidgetVariantEnum;
};

export enum PaymentApplicationEnum {
  MPOS = 'MPOS',
}

export type PaymentCaptureResponse = MutationResponseInterface & {
  __typename?: 'PaymentCaptureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PaymentDetails = {
  __typename?: 'PaymentDetails';
  bank?: Maybe<Scalars['String']>;
  cardId?: Maybe<Scalars['String']>;
  fee?: Maybe<Scalars['Int']>;
  international?: Maybe<Scalars['Boolean']>;
  invoiceId?: Maybe<Scalars['String']>;
  method?: Maybe<Scalars['String']>;
  refundStatus?: Maybe<Scalars['String']>;
  tax?: Maybe<Scalars['Int']>;
  vpa?: Maybe<Scalars['String']>;
  wallet?: Maybe<Scalars['String']>;
};

export type PaymentEmiDetails = {
  __typename?: 'PaymentEmiDetails';
  amount: Money;
  duration: Scalars['Float'];
  rate: Scalars['Float'];
};

export type PaymentError = {
  __typename?: 'PaymentError';
  code?: Maybe<Scalars['String']>;
  description?: Maybe<Scalars['String']>;
};

export type PaymentHandleWidget = {
  __typename?: 'PaymentHandleWidget';
  description: Scalars['String'];
  paymentHandle: MerchantPaymentHandle;
  title: Scalars['String'];
  type: PaymentsWidgetTypeEnum;
  variant: WidgetVariantEnum;
};

export type PaymentInstantRefundEligibilityAmount = {
  __typename?: 'PaymentInstantRefundEligibilityAmount';
  amount: Money;
  fee?: Maybe<Scalars['Float']>;
  tax?: Maybe<Scalars['Float']>;
};

export enum PaymentInstantRefundEligibilityOptionEnum {
  DEFAULT_OPTIMUM = 'DEFAULT_OPTIMUM',
  DISABLED = 'DISABLED',
  ENABLED = 'ENABLED',
  ONLY_OPTIMUM = 'ONLY_OPTIMUM',
}

export type PaymentInstantRefundEligibilityResponse = {
  __typename?: 'PaymentInstantRefundEligibilityResponse';
  isAllowed: Scalars['Boolean'];
  messages?: Maybe<Scalars['JSONObject']>;
  option: PaymentInstantRefundEligibilityOptionEnum;
  refund?: Maybe<PaymentInstantRefundEligibilityAmount>;
};

export type PaymentLink = {
  __typename?: 'PaymentLink';
  amount: PaymentLinkAmount;
  customer?: Maybe<Customer>;
  dates: PaymentLinkDate;
  description?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  isPartiallyPayable?: Maybe<Scalars['Boolean']>;
  notes?: Maybe<Scalars['JSONObject']>;
  notifyBy?: Maybe<PaymentLinkNotifyBy>;
  orderId?: Maybe<Scalars['String']>;
  payments?: Maybe<Array<Payment>>;
  referenceId?: Maybe<Scalars['String']>;
  reminder?: Maybe<PaymentLinkReminder>;
  status: PaymentLinkStatusEnum;
  url: Scalars['URL'];
  user?: Maybe<User>;
};

export type PaymentLinkAmount = {
  __typename?: 'PaymentLinkAmount';
  due?: Maybe<Money>;
  firstMinimumPartialAmount?: Maybe<Money>;
  generated: Money;
  paid?: Maybe<Money>;
};

export type PaymentLinkCancelResponse = MutationResponseInterface & {
  __typename?: 'PaymentLinkCancelResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PaymentLinkCreateResponse = MutationResponseInterface & {
  __typename?: 'PaymentLinkCreateResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  paymentLink: PaymentLink;
  success: Scalars['Boolean'];
};

export type PaymentLinkDate = {
  __typename?: 'PaymentLinkDate';
  cancelledAt?: Maybe<Scalars['DateTime']>;
  createdAt: Scalars['DateTime'];
  deletedAt?: Maybe<Scalars['DateTime']>;
  expireBy?: Maybe<Scalars['DateTime']>;
  expiredAt?: Maybe<Scalars['DateTime']>;
  updatedAt?: Maybe<Scalars['DateTime']>;
};

export type PaymentLinkNotifyBy = {
  __typename?: 'PaymentLinkNotifyBy';
  email?: Maybe<Scalars['Boolean']>;
  sms?: Maybe<Scalars['Boolean']>;
};

export type PaymentLinkNotifyByInput = {
  email?: InputMaybe<Scalars['Boolean']>;
  sms?: InputMaybe<Scalars['Boolean']>;
};

export enum PaymentLinkNotifyMedium {
  EMAIL = 'EMAIL',
  SMS = 'SMS',
}

export type PaymentLinkNotifyResponse = MutationResponseInterface & {
  __typename?: 'PaymentLinkNotifyResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PaymentLinkReminder = {
  __typename?: 'PaymentLinkReminder';
  isEnabled?: Maybe<Scalars['Boolean']>;
  status?: Maybe<PaymentLinkReminderStatusEnum>;
};

export enum PaymentLinkReminderStatusEnum {
  COMPLETED = 'COMPLETED',
  DISABLED = 'DISABLED',
  FAILED = 'FAILED',
  IN_PROGRESS = 'IN_PROGRESS',
  PENDING = 'PENDING',
}

export enum PaymentLinkStatusEnum {
  CANCELLED = 'CANCELLED',
  CREATED = 'CREATED',
  DELETED = 'DELETED',
  EXPIRED = 'EXPIRED',
  PAID = 'PAID',
  PARTIALLY_PAID = 'PARTIALLY_PAID',
}

export type PaymentLinksResponse = PaginationResponseInterface & {
  __typename?: 'PaymentLinksResponse';
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  paymentLinks: Array<PaymentLink>;
  total?: Maybe<Scalars['NonNegativeInt']>;
};

export type PaymentMethod =
  | PaymentMethodApp
  | PaymentMethodBankTransfer
  | PaymentMethodCard
  | PaymentMethodCardlessEmi
  | PaymentMethodEmandate
  | PaymentMethodEmi
  | PaymentMethodNetBanking
  | PaymentMethodPayLater
  | PaymentMethodUpiTransfer
  | PaymentMethodWallet;

export type PaymentMethodApp = {
  __typename?: 'PaymentMethodApp';
  provider?: Maybe<Scalars['String']>;
};

export type PaymentMethodBankTransfer = {
  __typename?: 'PaymentMethodBankTransfer';
  amount: Money;
  bankReference: Scalars['String'];
  id: Scalars['ID'];
  mode: Scalars['String'];
  payerBankAccount?: Maybe<PaymentPayerBankAccount>;
  virtualAccount: PaymentVirtualAccount;
};

export type PaymentMethodCard = {
  __typename?: 'PaymentMethodCard';
  category?: Maybe<PaymentMethodCardCategoryEnum>;
  expiry: PaymentMethodCardExpiry;
  id: Scalars['ID'];
  isEmi?: Maybe<Scalars['Boolean']>;
  isInternational?: Maybe<Scalars['Boolean']>;
  issuer?: Maybe<Scalars['String']>;
  lastFourDigits: Scalars['Int'];
  name?: Maybe<Scalars['String']>;
  network: Scalars['String'];
  type: PaymentMethodCardType;
};

export enum PaymentMethodCardCategoryEnum {
  BUSINESS = 'BUSINESS',
  CONSUMER = 'CONSUMER',
}

export type PaymentMethodCardExpiry = {
  __typename?: 'PaymentMethodCardExpiry';
  month?: Maybe<Scalars['Int']>;
  year?: Maybe<Scalars['Int']>;
};

export enum PaymentMethodCardType {
  CREDIT = 'CREDIT',
  DEBIT = 'DEBIT',
  PREPAID = 'PREPAID',
  UNKNOWN = 'UNKNOWN',
}

export type PaymentMethodCardlessEmi = {
  __typename?: 'PaymentMethodCardlessEmi';
  cardlessEmi?: Maybe<Scalars['String']>;
};

export type PaymentMethodEmandate = {
  __typename?: 'PaymentMethodEmandate';
  emandate?: Maybe<Scalars['String']>;
};

export type PaymentMethodEmi = {
  __typename?: 'PaymentMethodEmi';
  card: PaymentMethodCard;
  emi: PaymentEmiDetails;
};

export enum PaymentMethodEnum {
  AEPS = 'AEPS',
  BANK_TRANSFER = 'BANK_TRANSFER',
  CARD = 'CARD',
  CARDLESS_EMI = 'CARDLESS_EMI',
  EMANDATE = 'EMANDATE',
  EMI = 'EMI',
  NETBANKING = 'NETBANKING',
  PAY_LATER = 'PAY_LATER',
  UPI = 'UPI',
  WALLET = 'WALLET',
}

export type PaymentMethodNetBanking = {
  __typename?: 'PaymentMethodNetBanking';
  bankName: Scalars['String'];
};

export type PaymentMethodPayLater = {
  __typename?: 'PaymentMethodPayLater';
  payLater?: Maybe<Scalars['String']>;
};

export type PaymentMethodUpiTransfer = {
  __typename?: 'PaymentMethodUPITransfer';
  vpa?: Maybe<Scalars['String']>;
};

export type PaymentMethodWallet = {
  __typename?: 'PaymentMethodWallet';
  wallet?: Maybe<Scalars['String']>;
};

export type PaymentOverviewResponse = {
  __typename?: 'PaymentOverviewResponse';
  paymentCollected?: Maybe<PaymentAggregationSummary>;
  refundFailed?: Maybe<PaymentAggregationSummary>;
  refundProcessed?: Maybe<PaymentAggregationSummary>;
  refundProcessing?: Maybe<PaymentAggregationSummary>;
};

export type PaymentPage = {
  __typename?: 'PaymentPage';
  amount: PaymentPageAmount;
  dates: PaymentPageDate;
  description?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  notes?: Maybe<Scalars['JSONObject']>;
  orderId?: Maybe<Scalars['String']>;
  paymentPagesItems: Array<PaymentPageItem>;
  settings?: Maybe<PaymentPageSettings>;
  status: PaymentPageStatusEnum;
  support?: Maybe<PaymentPageSupportDetails>;
  terms?: Maybe<Scalars['String']>;
  timesPaid?: Maybe<Scalars['Int']>;
  timesPayable?: Maybe<Scalars['Int']>;
  title: Scalars['String'];
  url: Scalars['URL'];
  user?: Maybe<User>;
};

export type PaymentPageAmount = {
  __typename?: 'PaymentPageAmount';
  amount: Money;
  totalAmountPaid: Money;
};

export type PaymentPageDate = {
  __typename?: 'PaymentPageDate';
  createdAt: Scalars['DateTime'];
  expireBy?: Maybe<Scalars['DateTime']>;
  updatedAt?: Maybe<Scalars['DateTime']>;
};

export type PaymentPageItem = {
  __typename?: 'PaymentPageItem';
  entity: Scalars['String'];
  hsnCode?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  imageUrl?: Maybe<Scalars['URL']>;
  item?: Maybe<PageItem>;
  mandatory?: Maybe<Scalars['Boolean']>;
  minPurchase?: Maybe<Scalars['Int']>;
  paymentLinkId: Scalars['ID'];
  planId?: Maybe<Scalars['ID']>;
  quantitySold?: Maybe<Scalars['Int']>;
  stock?: Maybe<Scalars['Int']>;
};

export type PaymentPageSettings = {
  __typename?: 'PaymentPageSettings';
  allowSocialShare?: Maybe<Scalars['String']>;
  checkoutOptions?: Maybe<CheckoutOptions>;
  enable80GDetails?: Maybe<Scalars['String']>;
  enableCustomSerialNumber?: Maybe<Scalars['String']>;
  enableReceipt?: Maybe<Scalars['String']>;
  goalTracker?: Maybe<GoalTrackerSettings>;
  partnerWebhookSettings?: Maybe<PartnerWebhookSettings>;
  paymentButtonLabel?: Maybe<Scalars['String']>;
  paymentSuccessMessage?: Maybe<Scalars['String']>;
  paymentSuccessRedirectURL?: Maybe<Scalars['String']>;
  selectedUdfField?: Maybe<Scalars['String']>;
  theme?: Maybe<Scalars['String']>;
  trackingSettings?: Maybe<PpTrackingSettings>;
  udfSchema?: Maybe<Scalars['String']>;
  version?: Maybe<Scalars['String']>;
};

export enum PaymentPageStatusEnum {
  ACTIVE = 'ACTIVE',
  INACTIVE = 'INACTIVE',
}

export type PaymentPageSupportDetails = {
  __typename?: 'PaymentPageSupportDetails';
  email: Scalars['String'];
  phone: Scalars['String'];
};

export type PaymentPageTransaction = {
  __typename?: 'PaymentPageTransaction';
  acquirerData?: Maybe<PageAcquirerData>;
  amount?: Maybe<TransactionAmount>;
  createdAt?: Maybe<Scalars['DateTime']>;
  description?: Maybe<Scalars['String']>;
  entity?: Maybe<Scalars['String']>;
  error?: Maybe<TransactionError>;
  id: Scalars['String'];
  notes?: Maybe<Scalars['JSONObject']>;
  paymentDetails?: Maybe<PaymentDetails>;
  status?: Maybe<TransactionStatus>;
  userDetails?: Maybe<UserContactDetails>;
};

export type PaymentPageTransactionResponse = PaginationResponseInterface & {
  __typename?: 'PaymentPageTransactionResponse';
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  paymentPagesTransactions?: Maybe<Array<PaymentPageTransaction>>;
  total?: Maybe<Scalars['NonNegativeInt']>;
};

export type PaymentPagesResponse = PaginationResponseInterface & {
  __typename?: 'PaymentPagesResponse';
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  paymentPages: Array<PaymentPage>;
  total?: Maybe<Scalars['NonNegativeInt']>;
};

export type PaymentPayerBankAccount = {
  __typename?: 'PaymentPayerBankAccount';
  accountNumber: Scalars['BigInt'];
  bankName?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  ifsc?: Maybe<Scalars['String']>;
  name: Scalars['String'];
};

export type PaymentRefund = {
  __typename?: 'PaymentRefund';
  acquirerData?: Maybe<AcquirerData>;
  amount: Money;
  batchId?: Maybe<Scalars['String']>;
  createdAt: Scalars['DateTime'];
  entity: Scalars['String'];
  id: Scalars['ID'];
  notes?: Maybe<Scalars['JSONObject']>;
  paymentId: Scalars['ID'];
  processedAt?: Maybe<Scalars['DateTime']>;
  speed?: Maybe<PaymentRefundSpeed>;
  status: PaymentRefundStatusEnum;
};

export type PaymentRefundResponse = MutationResponseInterface & {
  __typename?: 'PaymentRefundResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PaymentRefundSpeed = {
  __typename?: 'PaymentRefundSpeed';
  processed?: Maybe<PaymentRefundSpeedProcessedEnum>;
  requested?: Maybe<PaymentRefundSpeedRequestedEnum>;
};

export enum PaymentRefundSpeedProcessedEnum {
  /** Indicates that the refund has been processed instantly via fund transfer. */
  INSTANT = 'INSTANT',
  /** Indicates that the refund has been processed by the payment processing partner. That is, the refund will take 5-7 working days. */
  NORMAL = 'NORMAL',
}

export enum PaymentRefundSpeedRequestedEnum {
  /** Indicates that the refund will be processed via the normal speed. That is, the refund will take 5-7 working days */
  NORMAL = 'NORMAL',
  /** Indicates that the refund will be processed at an optimal speed based on Razorpay's internal fund transfer logic */
  OPTIMUM = 'OPTIMUM',
}

export enum PaymentRefundStatusEnum {
  CREATED = 'CREATED',
  FAILED = 'FAILED',
  PENDING = 'PENDING',
  /** This is the terminal state of the refund */
  PROCESSED = 'PROCESSED',
  /** Indicates that Razorpay is attempting to process the refund */
  PROCESSING = 'PROCESSING',
}

export enum PaymentStatusEnum {
  AUTHORIZED = 'AUTHORIZED',
  CAPTURED = 'CAPTURED',
  CREATED = 'CREATED',
  FAILED = 'FAILED',
  PENDING = 'PENDING',
  REFUNDED = 'REFUNDED',
}

export enum PaymentSummaryAggregationFieldEnum {
  PAYMENT_BY_METHOD = 'PAYMENT_BY_METHOD',
  PAYMENT_COUNT = 'PAYMENT_COUNT',
  PAYMENT_SUM = 'PAYMENT_SUM',
  REFUND_COUNT_INSTANT = 'REFUND_COUNT_INSTANT',
  REFUND_COUNT_NORMAL = 'REFUND_COUNT_NORMAL',
  REFUND_SUM_INSTANT = 'REFUND_SUM_INSTANT',
  REFUND_SUM_NORMAL = 'REFUND_SUM_NORMAL',
}

export enum PaymentSummaryFilterFieldEnum {
  METHOD_FILTER = 'METHOD_FILTER',
  REFUND_INSTANT_SPEED = 'REFUND_INSTANT_SPEED',
  REFUND_NORMAL_SPEED = 'REFUND_NORMAL_SPEED',
}

export enum PaymentSummaryIndexEnum {
  PAYMENTS = 'PAYMENTS',
  REFUNDS = 'REFUNDS',
}

export type PaymentSummaryResponse = {
  __typename?: 'PaymentSummaryResponse';
  data?: Maybe<Array<AggregationResultType>>;
  total?: Maybe<Scalars['PositiveInt']>;
  updatedAt: Scalars['DateTime'];
};

export type PaymentTerm = {
  __typename?: 'PaymentTerm';
  days?: Maybe<Scalars['PositiveInt']>;
};

export type PaymentVirtualAccount = {
  __typename?: 'PaymentVirtualAccount';
  amount: PaymentVirtualAccountAmount;
  customer?: Maybe<Customer>;
  dates: PaymentVirtualAccountDates;
  description?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  name: Scalars['String'];
  notes?: Maybe<Array<Maybe<Scalars['JSONObject']>>>;
  status: Scalars['String'];
};

export type PaymentVirtualAccountAmount = {
  __typename?: 'PaymentVirtualAccountAmount';
  expected?: Maybe<Money>;
  paid: Money;
};

export type PaymentVirtualAccountDates = {
  __typename?: 'PaymentVirtualAccountDates';
  closeBy?: Maybe<Scalars['DateTime']>;
  closedAt?: Maybe<Scalars['DateTime']>;
  createdAt: Scalars['DateTime'];
};

export type PaymentsNewLaunchProductViewUpdate = MutationResponseInterface & {
  __typename?: 'PaymentsNewLaunchProductViewUpdate';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PaymentsProductFtuxUpdateResponse = MutationResponseInterface & {
  __typename?: 'PaymentsProductFtuxUpdateResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PaymentsResponse = PaginationResponseInterface & {
  __typename?: 'PaymentsResponse';
  hasMore: Scalars['Boolean'];
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  payments: Array<Payment>;
  total: Scalars['NonNegativeInt'];
};

export enum PaymentsSegmentEnum {
  PAYMENTS_ENABLED_AND_FREQUENTLY_TRANSACTED = 'PAYMENTS_ENABLED_AND_FREQUENTLY_TRANSACTED',
  PAYMENTS_ENABLED_AND_NOT_TRANSACTED = 'PAYMENTS_ENABLED_AND_NOT_TRANSACTED',
  PAYMENTS_ENABLED_AND_TRANSACTED = 'PAYMENTS_ENABLED_AND_TRANSACTED',
  PAYMENTS_NOT_ENABLED = 'PAYMENTS_NOT_ENABLED',
}

export type PaymentsWidgetError = {
  __typename?: 'PaymentsWidgetError';
  errorCode: Scalars['String'];
  errorDescription: Scalars['String'];
  type: PaymentsWidgetTypeEnum;
};

export enum PaymentsWidgetTypeEnum {
  ACCEPT_PAYMENTS = 'ACCEPT_PAYMENTS',
  ONBOARDING_CARD = 'ONBOARDING_CARD',
  PAYMENT_ANALYTICS = 'PAYMENT_ANALYTICS',
  PAYMENT_HANDLE = 'PAYMENT_HANDLE',
  RECENT_TRANSACTIONS = 'RECENT_TRANSACTIONS',
  SETTLEMENTS = 'SETTLEMENTS',
}

export type PaymentsWidgets = {
  __typename?: 'PaymentsWidgets';
  segment: PaymentsSegmentEnum;
  widgets: Array<Widget>;
};

export type Payout = {
  __typename?: 'Payout';
  amount: Money;
  bankingAccount?: Maybe<MerchantBankingAccount>;
  batchId?: Maybe<Scalars['String']>;
  cancelledBy?: Maybe<User>;
  dates?: Maybe<PayoutDate>;
  failureReason?: Maybe<Scalars['String']>;
  fee?: Maybe<PayoutFee>;
  fundAccount?: Maybe<MerchantContactFundAccount>;
  id: Scalars['ID'];
  internalStatus?: Maybe<PayoutInternalStatusEnum>;
  isPendingOnMe: Scalars['Boolean'];
  mode: PayoutModeEnum;
  narration?: Maybe<Scalars['String']>;
  notes?: Maybe<Scalars['JSONObject']>;
  pendingReason?: Maybe<Scalars['String']>;
  purpose: Scalars['String'];
  referenceId?: Maybe<Scalars['String']>;
  remark?: Maybe<Scalars['String']>;
  sources: Array<PayoutSource>;
  status: PayoutStatusEnum;
  statusDetails?: Maybe<Scalars['JSONObject']>;
  tax?: Maybe<Money>;
  transaction?: Maybe<Transaction>;
  user?: Maybe<User>;
  utr?: Maybe<Scalars['String']>;
  workflow?: Maybe<PayoutWorkflow>;
};

export type PayoutApproveBulkResponse =
  | PayoutApproveBulkResponseFailure
  | PayoutApproveBulkResponseSuccess;

export type PayoutApproveBulkResponseFailure = {
  __typename?: 'PayoutApproveBulkResponseFailure';
  code: Scalars['PositiveInt'];
  failedPayoutIds?: Maybe<Array<Scalars['ID']>>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PayoutApproveBulkResponseSuccess = {
  __typename?: 'PayoutApproveBulkResponseSuccess';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PayoutBatch = {
  __typename?: 'PayoutBatch';
  bankingAccount?: Maybe<MerchantBankingAccount>;
  batchType: PayoutBatchTypeEnum;
  createdBy: User;
  dates: PayoutBatchDates;
  id: Scalars['ID'];
  isPendingOnMe?: Maybe<Scalars['Boolean']>;
  name: Scalars['String'];
  payoutPurpose?: Maybe<Scalars['String']>;
  payoutsCount?: Maybe<PayoutBatchPayoutsCount>;
  processedAmount: Money;
  processingBatchId?: Maybe<Scalars['ID']>;
  status: PayoutBatchStatusEnum;
  totalAmount: Money;
  validationBatchId?: Maybe<Scalars['ID']>;
};

export type PayoutBatchDates = {
  __typename?: 'PayoutBatchDates';
  createdAt: Scalars['DateTime'];
  failedAt?: Maybe<Scalars['DateTime']>;
  pendingAt?: Maybe<Scalars['DateTime']>;
  processedAt?: Maybe<Scalars['DateTime']>;
  processingAt?: Maybe<Scalars['DateTime']>;
  rejectedAt?: Maybe<Scalars['DateTime']>;
  validatedAt?: Maybe<Scalars['DateTime']>;
  validatingAt?: Maybe<Scalars['DateTime']>;
};

export type PayoutBatchPayoutsCount = {
  __typename?: 'PayoutBatchPayoutsCount';
  failure: Scalars['NonNegativeInt'];
  processed: Scalars['NonNegativeInt'];
  success: Scalars['NonNegativeInt'];
  total: Scalars['NonNegativeInt'];
  validated: Scalars['NonNegativeInt'];
};

export enum PayoutBatchStatusEnum {
  CREATED = 'CREATED',
  FAILED = 'FAILED',
  PENDING = 'PENDING',
  PROCESSED = 'PROCESSED',
  PROCESSING = 'PROCESSING',
  REJECTED = 'REJECTED',
  VALIDATED = 'VALIDATED',
  VALIDATING = 'VALIDATING',
}

export enum PayoutBatchTypeEnum {
  AMAZON_PAY_DETAILS = 'AMAZON_PAY_DETAILS',
  AMAZON_PAY_ID = 'AMAZON_PAY_ID',
  BANK_ACCOUNT_DETAILS = 'BANK_ACCOUNT_DETAILS',
  BANK_ACCOUNT_ID = 'BANK_ACCOUNT_ID',
  PAYOUT = 'PAYOUT',
  UPI_DETAILS = 'UPI_DETAILS',
  UPI_ID = 'UPI_ID',
}

export type PayoutBatchesResponse = PaginationResponseInterface & {
  __typename?: 'PayoutBatchesResponse';
  hasMore?: Maybe<Scalars['Boolean']>;
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  payoutBatches: Array<PayoutBatch>;
  total?: Maybe<Scalars['NonNegativeInt']>;
};

export type PayoutCompositeCreateResponse = MutationResponseInterface & {
  __typename?: 'PayoutCompositeCreateResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  payout?: Maybe<Payout>;
  success: Scalars['Boolean'];
};

export type PayoutCompositeMerchantContactInput = {
  name: Scalars['String'];
};

export type PayoutCreateIciciResponse = MutationResponseInterface & {
  __typename?: 'PayoutCreateIciciResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  payoutId?: Maybe<Scalars['ID']>;
  success: Scalars['Boolean'];
};

export type PayoutCreateResponse = MutationResponseInterface & {
  __typename?: 'PayoutCreateResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  payout?: Maybe<Payout>;
  success: Scalars['Boolean'];
};

export type PayoutDate = {
  __typename?: 'PayoutDate';
  cancelledAt?: Maybe<Scalars['DateTime']>;
  createdAt: Scalars['DateTime'];
  failedAt?: Maybe<Scalars['DateTime']>;
  initiatedAt?: Maybe<Scalars['DateTime']>;
  pendingAt?: Maybe<Scalars['DateTime']>;
  processedAt?: Maybe<Scalars['DateTime']>;
  queuedAt?: Maybe<Scalars['DateTime']>;
  rejectedAt?: Maybe<Scalars['DateTime']>;
  reversedAt?: Maybe<Scalars['DateTime']>;
  scheduledAt?: Maybe<Scalars['DateTime']>;
  scheduledOn?: Maybe<Scalars['DateTime']>;
};

export type PayoutFee = {
  __typename?: 'PayoutFee';
  amount: Money;
  type?: Maybe<PayoutFeeEnum>;
};

export enum PayoutFeeEnum {
  FREE_PAYOUT = 'FREE_PAYOUT',
  REWARD_FEE = 'REWARD_FEE',
}

export enum PayoutInternalStatusEnum {
  FAILED = 'FAILED',
  INITIATED = 'INITIATED',
  PENDING = 'PENDING',
  PENDING_ON_OTP = 'PENDING_ON_OTP',
}

export type PayoutLink = {
  __typename?: 'PayoutLink';
  amount: Money;
  attemptCount: Scalars['NonNegativeInt'];
  dates?: Maybe<PayoutLinkDate>;
  description: Scalars['String'];
  fundAccount?: Maybe<MerchantContactFundAccount>;
  id: Scalars['ID'];
  merchantContact: MerchantContact;
  notes?: Maybe<Scalars['JSONObject']>;
  payouts: Array<Payout>;
  purpose: Scalars['String'];
  referenceId?: Maybe<Scalars['String']>;
  sentVia: PayoutLinkSentVia;
  shortUrl?: Maybe<Scalars['String']>;
  status: PayoutLinkStatusEnum;
  user?: Maybe<User>;
};

export type PayoutLinkPayoutsArgs = {
  limit?: InputMaybe<Scalars['PositiveInt']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
};

export type PayoutLinkCreateResponse = MutationResponseInterface & {
  __typename?: 'PayoutLinkCreateResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  payoutLink?: Maybe<PayoutLink>;
  success: Scalars['Boolean'];
};

export type PayoutLinkDate = {
  __typename?: 'PayoutLinkDate';
  cancelledAt?: Maybe<Scalars['DateTime']>;
  createdAt: Scalars['DateTime'];
  expiredAt?: Maybe<Scalars['DateTime']>;
};

export type PayoutLinkSendVia = {
  email?: InputMaybe<Scalars['Boolean']>;
  sms?: InputMaybe<Scalars['Boolean']>;
};

export type PayoutLinkSentVia = {
  __typename?: 'PayoutLinkSentVia';
  email: Scalars['Boolean'];
  sms: Scalars['Boolean'];
};

export enum PayoutLinkStatusEnum {
  ATTEMPTED = 'ATTEMPTED',
  CANCELLED = 'CANCELLED',
  EXPIRED = 'EXPIRED',
  ISSUED = 'ISSUED',
  PENDING = 'PENDING',
  PROCESSED = 'PROCESSED',
  PROCESSING = 'PROCESSING',
  REJECTED = 'REJECTED',
}

export type PayoutLinksResponse = PaginationResponseInterface & {
  __typename?: 'PayoutLinksResponse';
  hasMore?: Maybe<Scalars['Boolean']>;
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  payoutLinks: Array<PayoutLink>;
  total?: Maybe<Scalars['NonNegativeInt']>;
};

export enum PayoutModeEnum {
  AMAZON_PAY = 'AMAZON_PAY',
  CARD_TRANSFER = 'CARD_TRANSFER',
  IMPS = 'IMPS',
  INTERNAL_FUND_TRANSFER = 'INTERNAL_FUND_TRANSFER',
  NEFT = 'NEFT',
  RTGS = 'RTGS',
  UPI = 'UPI',
}

export type PayoutPendingOnInput = {
  roles?: InputMaybe<Array<PayoutPendingOnRoleEnum>>;
};

export enum PayoutPendingOnRoleEnum {
  ADMIN = 'ADMIN',
  FINANCE_L1 = 'FINANCE_L1',
  FINANCE_L2 = 'FINANCE_L2',
  FINANCE_L3 = 'FINANCE_L3',
  OWNER = 'OWNER',
}

export type PayoutPurpose = {
  __typename?: 'PayoutPurpose';
  label: Scalars['String'];
  type: PayoutPurposeTypeEnum;
};

export type PayoutPurposeCreateResponse = MutationResponseInterface & {
  __typename?: 'PayoutPurposeCreateResponse';
  code: Scalars['PositiveInt'];
  message: Scalars['String'];
  success: Scalars['Boolean'];
};

export enum PayoutPurposeTypeEnum {
  REFUND = 'REFUND',
  SETTLEMENT = 'SETTLEMENT',
}

export type PayoutRejectBulkResponse =
  | PayoutRejectBulkResponseFailure
  | PayoutRejectBulkResponseSuccess;

export type PayoutRejectBulkResponseFailure = {
  __typename?: 'PayoutRejectBulkResponseFailure';
  code: Scalars['PositiveInt'];
  failedPayoutIds?: Maybe<Array<Scalars['ID']>>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PayoutRejectBulkResponseSuccess = {
  __typename?: 'PayoutRejectBulkResponseSuccess';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PayoutSource = {
  __typename?: 'PayoutSource';
  priority?: Maybe<Scalars['BigInt']>;
  sourceId: Scalars['String'];
  sourceType: Scalars['String'];
};

export enum PayoutStatusEnum {
  CANCELLED = 'CANCELLED',
  FAILED = 'FAILED',
  PENDING = 'PENDING',
  PROCESSED = 'PROCESSED',
  PROCESSING = 'PROCESSING',
  QUEUED = 'QUEUED',
  REJECTED = 'REJECTED',
  REVERSED = 'REVERSED',
  SCHEDULED = 'SCHEDULED',
}

export type PayoutWorkflow = PayoutWorkflowHistory | Workflow;

export type PayoutWorkflowHistory = {
  __typename?: 'PayoutWorkflowHistory';
  currentLevel: Scalars['Int'];
  steps: Array<PayoutWorkflowStep>;
};

export type PayoutWorkflowRole = {
  __typename?: 'PayoutWorkflowRole';
  checkers?: Maybe<Array<PayoutWorkflowRoleChecker>>;
  id: Scalars['ID'];
  reviewerCount: Scalars['Int'];
  type: PayoutWorkflowRoleTypeEnum;
};

export type PayoutWorkflowRoleChecker = {
  __typename?: 'PayoutWorkflowRoleChecker';
  approved: Scalars['Boolean'];
  comment?: Maybe<Scalars['String']>;
  email?: Maybe<Scalars['EmailAddress']>;
  id: Scalars['ID'];
  name?: Maybe<Scalars['String']>;
  user?: Maybe<User>;
};

export enum PayoutWorkflowRoleTypeEnum {
  ADMIN = 'ADMIN',
  FINANCE_L1 = 'FINANCE_L1',
  FINANCE_L2 = 'FINANCE_L2',
  FINANCE_L3 = 'FINANCE_L3',
  OPERATIONS = 'OPERATIONS',
  OWNER = 'OWNER',
  VIEW_ONLY = 'VIEW_ONLY',
}

export type PayoutWorkflowStep = {
  __typename?: 'PayoutWorkflowStep';
  id: Scalars['ID'];
  level: Scalars['PositiveInt'];
  operationType?: Maybe<PayoutWorkflowStepOperationTypeEnum>;
  roles: Array<PayoutWorkflowRole>;
};

export enum PayoutWorkflowStepOperationTypeEnum {
  AND = 'AND',
  OR = 'OR',
}

export type PayoutsPendingSummary = {
  __typename?: 'PayoutsPendingSummary';
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
};

export enum PayoutsQueuedReasonEnum {
  BENEFICIARY_BANK_DOWN = 'BENEFICIARY_BANK_DOWN',
  LOW_BALANCE = 'LOW_BALANCE',
  NEFT_LIMIT_EXHAUSTED = 'NEFT_LIMIT_EXHAUSTED',
  NEFT_WINDOW_CLOSED = 'NEFT_WINDOW_CLOSED',
  NPCI_DOWN = 'NPCI_DOWN',
}

export type PayoutsQueuedSummary =
  | PayoutsQueuedSummaryBeneficiaryBankDown
  | PayoutsQueuedSummaryLowBalance
  | PayoutsQueuedSummaryNeftLimitExhausted
  | PayoutsQueuedSummaryNeftWindowClosed
  | PayoutsQueuedSummaryNpciSystemDown
  | PayoutsQueuedSummaryWithoutReason;

export type PayoutsQueuedSummaryBeneficiaryBankDown = {
  __typename?: 'PayoutsQueuedSummaryBeneficiaryBankDown';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsQueuedSummaryLowBalance = {
  __typename?: 'PayoutsQueuedSummaryLowBalance';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsQueuedSummaryNeftLimitExhausted = {
  __typename?: 'PayoutsQueuedSummaryNEFTLimitExhausted';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsQueuedSummaryNeftWindowClosed = {
  __typename?: 'PayoutsQueuedSummaryNEFTWindowClosed';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsQueuedSummaryNpciSystemDown = {
  __typename?: 'PayoutsQueuedSummaryNPCISystemDown';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsQueuedSummaryWithoutReason = {
  __typename?: 'PayoutsQueuedSummaryWithoutReason';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsResponse = PaginationResponseInterface & {
  __typename?: 'PayoutsResponse';
  hasMore?: Maybe<Scalars['Boolean']>;
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  payouts: Array<Payout>;
  total?: Maybe<Scalars['NonNegativeInt']>;
};

export enum PayoutsScheduledPeriodEnum {
  ALL_TIME = 'ALL_TIME',
  NEXT_MONTH = 'NEXT_MONTH',
  NEXT_TWO_DAYS = 'NEXT_TWO_DAYS',
  NEXT_WEEK = 'NEXT_WEEK',
  TODAY = 'TODAY',
}

export type PayoutsScheduledSummary =
  | PayoutsScheduledSummaryAllTime
  | PayoutsScheduledSummaryNextMonth
  | PayoutsScheduledSummaryNextTwoDays
  | PayoutsScheduledSummaryNextWeek
  | PayoutsScheduledSummaryToday;

export type PayoutsScheduledSummaryAllTime = {
  __typename?: 'PayoutsScheduledSummaryAllTime';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsScheduledSummaryNextMonth = {
  __typename?: 'PayoutsScheduledSummaryNextMonth';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsScheduledSummaryNextTwoDays = {
  __typename?: 'PayoutsScheduledSummaryNextTwoDays';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsScheduledSummaryNextWeek = {
  __typename?: 'PayoutsScheduledSummaryNextWeek';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsScheduledSummaryToday = {
  __typename?: 'PayoutsScheduledSummaryToday';
  balance: Money;
  count: Scalars['NonNegativeInt'];
  totalAmount: Money;
  totalFees: Money;
};

export type PayoutsSummary = {
  __typename?: 'PayoutsSummary';
  bankingAccount: MerchantBankingAccount;
  pending: PayoutsPendingSummary;
  queued: Array<PayoutsQueuedSummary>;
  scheduled: Array<PayoutsScheduledSummary>;
};

export type Phone = {
  __typename?: 'Phone';
  countryCode?: Maybe<Scalars['String']>;
  number?: Maybe<Scalars['String']>;
};

export type PhoneInput = {
  countryCode?: InputMaybe<Scalars['String']>;
  number: Scalars['String'];
};

export enum PlatformEnum {
  ANDROID = 'ANDROID',
  IOS = 'IOS',
}

export type PointOfSale = {
  __typename?: 'PointOfSale';
  id: Scalars['ID'];
  ssoIdentifier: Scalars['ID'];
  url: Scalars['URL'];
  userId: Scalars['ID'];
};

export type PointOfSaleKeyFetchResponse = {
  __typename?: 'PointOfSaleKeyFetchResponse';
  accessKey: Scalars['String'];
  secretKey: Scalars['String'];
};

export type PointOfSalePaymentCreateFailureResponse = MutationResponseInterface & {
  __typename?: 'PointOfSalePaymentCreateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PointOfSalePaymentCreateResponse =
  | PointOfSalePaymentCreateFailureResponse
  | PointOfSalePaymentCreateSuccessResponse;

export type PointOfSalePaymentCreateSuccessResponse = MutationResponseInterface & {
  __typename?: 'PointOfSalePaymentCreateSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  pointOfSale: PointOfSale;
  success: Scalars['Boolean'];
};

export type PointOfSalePaymentTransactionInput = {
  authorizationCode?: InputMaybe<Scalars['String']>;
  description?: InputMaybe<Scalars['String']>;
  paymentStatus: PointOfSalePaymentTransactionStatus;
  resultCode: Scalars['String'];
  retrievalReferenceNumber?: InputMaybe<Scalars['String']>;
  statusCode?: InputMaybe<Scalars['String']>;
  transactionId?: InputMaybe<Scalars['String']>;
};

export enum PointOfSalePaymentTransactionStatus {
  FAILURE = 'FAILURE',
  SUCCESS = 'SUCCESS',
}

export type PointOfSalePaymentUpdateFailureResponse = MutationResponseInterface & {
  __typename?: 'PointOfSalePaymentUpdateFailureResponse';
  code: Scalars['PositiveInt'];
  gatewayErrorCode: Scalars['String'];
  gatewayErrorDescription: Scalars['String'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type PointOfSalePaymentUpdateResponse =
  | PointOfSalePaymentUpdateFailureResponse
  | PointOfSalePaymentUpdateSuccessResponse;

export type PointOfSalePaymentUpdateSuccessResponse = MutationResponseInterface & {
  __typename?: 'PointOfSalePaymentUpdateSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum ProductTypeEnum {
  PGMOBILE = 'PGMOBILE',
  RAZORPAYX = 'RAZORPAYX',
}

export type QrCode = {
  __typename?: 'QRCode';
  /** QR code date details */
  dates: QrCodeDate;
  /** A brief description about the QR Code */
  description?: Maybe<Scalars['String']>;
  /** Unique identifier of the QR Code */
  id: Scalars['ID'];
  /** The URL of the QR Code */
  imageURL: Scalars['String'];
  /** Indicates if the QR Code should accept payments of specific amounts or any amount */
  isFixedAmount?: Maybe<Scalars['Boolean']>;
  /** Label entered to identify the QR Code */
  name?: Maybe<Scalars['String']>;
  /** QR Code payment details */
  paymentDetails: QrCodePaymentDetail;
  /** Indicates the status of the QR Code */
  status: QrCodeStatusEnum;
  /** The type of the QR Code */
  type: QrCodeTypeEnum;
  /** Indicates if the QR Code should be allowed to accept single payment or multiple payments */
  usage: QrCodeUsageEnum;
};

export type QrCodeCreateFailureResponse = MutationResponseInterface & {
  __typename?: 'QRCodeCreateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type QrCodeCreateResponse = QrCodeCreateFailureResponse | QrCodeCreateSuccessResponse;

export type QrCodeCreateSuccessResponse = MutationResponseInterface & {
  __typename?: 'QRCodeCreateSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  qrCode: QrCode;
  success: Scalars['Boolean'];
};

export type QrCodeDate = {
  __typename?: 'QRCodeDate';
  /** Unix timestamp at which the QR Code is created */
  createdAt?: Maybe<Scalars['DateTime']>;
};

export type QrCodePaymentDetail = {
  __typename?: 'QRCodePaymentDetail';
  /** The amount allowed for a transaction. If specified, then any transaction of an amount less than or more than this value is not allowed. */
  paymentAmount?: Maybe<Money>;
  /** The total amount received on the QR Code. Only captured payments are considered */
  paymentAmountReceived?: Maybe<Money>;
  /** The total number of captured payments received on the QR Code */
  paymentsReceivedCount?: Maybe<Scalars['NonNegativeInt']>;
};

export enum QrCodeStatusEnum {
  ACTIVE = 'ACTIVE',
  CLOSED = 'CLOSED',
}

export enum QrCodeTypeEnum {
  BHARAT_QR = 'BHARAT_QR',
  UPI_QR = 'UPI_QR',
}

export enum QrCodeUsageEnum {
  MULTIPLE_USE = 'MULTIPLE_USE',
  SINGLE_USE = 'SINGLE_USE',
}

export type QrCodesResponse = PaginationResponseInterface & {
  __typename?: 'QRCodesResponse';
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  qrCodes: Array<QrCode>;
  total?: Maybe<Scalars['NonNegativeInt']>;
};

export type Query = {
  __typename?: 'Query';
  /** @deprecated Use aadhaarCaptchaV2 instead */
  aadhaarCaptcha: AadhaarCaptchaResponse;
  aadhaarCaptchaV2: AadhaarCaptchaV2Response;
  /** fetch state and city information from pincode */
  addressByPincode: AddressByPincodeResponse;
  bankDetails: Bank;
  failedPaymentsOverview: FailedPaymentsOverviewResponse;
  invoiceById: Invoice;
  invoices: InvoicesResponse;
  merchantAnalytics: MerchantAnalytics;
  merchantBalanceById: MerchantBalance;
  merchantBankDetails: MerchantBankDetailsResponse;
  merchantBankingAccountsBalance: MerchantBankingAccountsBalanceResponse;
  merchantBankingRoles: Array<Maybe<MerchantBankingRole>>;
  merchantBusinessCategories: Array<MerchantBusinessCategoriesResponse>;
  merchantBusinessParentCategories: Array<MerchantBusinessParentCategory>;
  merchantBusinessTypes: MerchantBusinessTypesResponse;
  merchantById: Merchant;
  merchantClarificationDetails?: Maybe<MerchantClarificationDetailsResponse>;
  merchantConfig: MerchantConfig;
  merchantConsent: MerchantConsentResponse;
  merchantContactById?: Maybe<MerchantContact>;
  merchantContactFundAccounts: MerchantContactFundAccountsResponse;
  merchantContactTypes: Array<Scalars['String']>;
  merchantContacts: MerchantContactsResponse;
  merchantCreditBalance: MerchantCreditBalanceResponse;
  merchantDocumentById: MerchantDocumentByIdResponse;
  merchantFeatureFlags?: Maybe<Array<MerchantFeatureFlag>>;
  merchantGstins: Array<Scalars['String']>;
  merchantIdentity?: Maybe<Array<MerchantIdentityResponse>>;
  merchantKYCPartnerAccess: MerchantKycPartnerAccessResponse;
  merchantNeedsClarificationEligibility: MerchantNcEligibilityResponse;
  merchantOnboardingQuestionDetails: MerchantOnboardingQuestionDetailsResponse;
  merchantPaymentHandle: MerchantPaymentHandleResponse;
  /** Query to check payment handle availablity */
  merchantPaymentHandleAvailability: MerchantPaymentHandleAvailabilityResponse;
  /** Query to fetch payment handle suggestions based on merchant's billing label */
  merchantPaymentHandleSuggestions: MerchantPaymentHandleSuggestionsResponse;
  merchantPolicy: MerchantPolicyResponse;
  merchantPolicyPreview: MerchantPolicyPreviewResponse;
  merchantPolicyPreviewV2: MerchantPolicyPreviewV2Response;
  merchantPolicyWizardV2Eligibility: MerchantPolicyWizardV2EligibilityResponse;
  merchantPreferences: Array<Maybe<MerchantPreference>>;
  /** Query to fetch M2M referral data for a merchant */
  merchantReferral: MerchantReferralResponse;
  /** Query to fetch the self serve workflow details */
  merchantSelfServeWorkflowStatus: MerchantSelfServeWorkflowStatusResponse;
  merchantSettlementConfig: MerchantSettlementConfigResponse;
  merchantSupportDetails: MerchantSupportDetailsResponse;
  merchantValidateSocialMediaUrl: MerchantValidateSocialMediaUrlResponse;
  merchantWebsites: MerchantWebsitesResponse;
  organisationInformation: Organisation;
  organisationInformationByDomain: Organisation;
  partnerConfigById: PartnerConfigResponse;
  paymentAnalytics: PaymentAnalyticsResponse;
  paymentById: Payment;
  paymentInstantRefundEligibility: PaymentInstantRefundEligibilityResponse;
  paymentLinkById: PaymentLink;
  paymentLinks: PaymentLinksResponse;
  paymentOverview: PaymentOverviewResponse;
  paymentPageById: PaymentPage;
  paymentPageTransactionsById?: Maybe<PaymentPageTransactionResponse>;
  paymentPages?: Maybe<PaymentPagesResponse>;
  paymentSummary: PaymentSummaryResponse;
  payments: PaymentsResponse;
  paymentsWidgets: PaymentsWidgets;
  payoutBatchById: PayoutBatch;
  payoutBatches: PayoutBatchesResponse;
  payoutById: Payout;
  payoutLinkById: PayoutLink;
  payoutLinks: PayoutLinksResponse;
  payoutPurposes: Array<PayoutPurpose>;
  payouts: PayoutsResponse;
  payoutsSummary: Array<PayoutsSummary>;
  payoutsWorkflowConfig?: Maybe<WorkflowConfig>;
  pointOfSaleKeysFetch: PointOfSaleKeyFetchResponse;
  /** Query to fetch list of QR Codes merchant has created */
  qrCodes: QrCodesResponse;
  refundById: PaymentRefund;
  refunds: RefundsResponse;
  settlementById: Settlement;
  settlementByUtr: Array<Maybe<Settlement>>;
  settlementCycle: SettlementCycle;
  settlements: SettlementsResponse;
  /** To get status of sms notification */
  smsNotificationStatus: SmsNotificationStatusResponse;
  tdsCategories: Array<TdsCategory>;
  transactionById: Transaction;
  transactions: TransactionsResponse;
  /** Query to check if 2FA should be through password */
  twoFactorPasswordEnabled: TwoFactorPasswordEnabledResponse;
  userAuthentication: UserAuthentication;
  userById: User;
  validateVpa: ValidateVpaResponse;
  vendorPaymentById: VendorPayment;
  vendorPayments: VendorPaymentsResponse;
  /** To get status of whatsapp notification */
  whatsappNotificationStatus: WhatsappNotificationStatusResponse;
};

export type QueryAddressByPincodeArgs = {
  pincode: Scalars['String'];
};

export type QueryBankDetailsArgs = {
  ifsc: Scalars['String'];
};

export type QueryFailedPaymentsOverviewArgs = {
  entity: Scalars['String'];
  fromDate: Scalars['DateTime'];
  limit: Scalars['PositiveInt'];
  toDate: Scalars['DateTime'];
};

export type QueryInvoiceByIdArgs = {
  id: Scalars['ID'];
};

export type QueryInvoicesArgs = {
  customer?: InputMaybe<CustomerInput>;
  fromDate?: InputMaybe<Scalars['DateTime']>;
  limit?: InputMaybe<Scalars['PositiveInt']>;
  notes?: InputMaybe<Scalars['String']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
  receiptNumber?: InputMaybe<Scalars['String']>;
  status?: InputMaybe<Array<InvoiceStatusEnum>>;
  toDate?: InputMaybe<Scalars['DateTime']>;
  types?: InputMaybe<Array<InvoiceTypeEnum>>;
};

export type QueryMerchantBalanceByIdArgs = {
  id: Scalars['ID'];
};

export type QueryMerchantBankingAccountsBalanceArgs = {
  cached?: InputMaybe<Scalars['Boolean']>;
  id?: InputMaybe<Scalars['ID']>;
  type: Scalars['String'];
};

export type QueryMerchantBusinessCategoriesArgs = {
  searchQuery?: InputMaybe<Scalars['String']>;
};

export type QueryMerchantByIdArgs = {
  id: Scalars['ID'];
};

export type QueryMerchantConfigArgs = {
  namespace: Scalars['String'];
};

export type QueryMerchantConsentArgs = {
  partnerId: Scalars['ID'];
};

export type QueryMerchantContactByIdArgs = {
  id: Scalars['ID'];
};

export type QueryMerchantContactFundAccountsArgs = {
  contactId: Scalars['ID'];
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
};

export type QueryMerchantContactsArgs = {
  active: Scalars['Boolean'];
  email?: InputMaybe<Scalars['EmailAddress']>;
  emailText?: InputMaybe<Scalars['String']>;
  fromDate?: InputMaybe<Scalars['DateTime']>;
  fundAccountId?: InputMaybe<Scalars['String']>;
  limit?: InputMaybe<Scalars['PositiveInt']>;
  name?: InputMaybe<Scalars['String']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
  phone?: InputMaybe<PhoneInput>;
  reference?: InputMaybe<Scalars['String']>;
  toDate?: InputMaybe<Scalars['DateTime']>;
  type?: InputMaybe<Scalars['String']>;
};

export type QueryMerchantDocumentByIdArgs = {
  documentId: Scalars['ID'];
};

export type QueryMerchantFeatureFlagsArgs = {
  names: Array<Scalars['String']>;
};

export type QueryMerchantIdentityArgs = {
  searchQuery: Scalars['String'];
};

export type QueryMerchantKycPartnerAccessArgs = {
  referralCode: Scalars['String'];
};

export type QueryMerchantPaymentHandleAvailabilityArgs = {
  paymentHandleSlug: Scalars['String'];
};

export type QueryMerchantPaymentHandleSuggestionsArgs = {
  suggestionsCountInput?: InputMaybe<Scalars['PositiveInt']>;
};

export type QueryMerchantPolicyArgs = {
  publishedUrl: Scalars['URL'];
  section: MerchantWebsiteSectionEnum;
};

export type QueryMerchantPolicyPreviewArgs = {
  section: MerchantWebsiteSectionEnum;
};

export type QueryMerchantPreferencesArgs = {
  preferenceGroup: Scalars['String'];
  preferenceType?: InputMaybe<Scalars['String']>;
};

export type QueryMerchantSelfServeWorkflowStatusArgs = {
  workflow: MerchantSelfServeWorkflowEnum;
};

export type QueryMerchantValidateSocialMediaUrlArgs = {
  platform: Scalars['String'];
  url: Scalars['URL'];
};

export type QueryOrganisationInformationArgs = {
  domainName: Scalars['String'];
};

export type QueryOrganisationInformationByDomainArgs = {
  domain: Scalars['String'];
};

export type QueryPartnerConfigByIdArgs = {
  id: Scalars['ID'];
};

export type QueryPaymentAnalyticsArgs = {
  aggregateBy: PaymentAnalyticsAggregateByEnum;
  columnName: Scalars['String'];
  filterBy?: InputMaybe<PaymentAnalyticsFilterBy>;
  fromDate: Scalars['DateTime'];
  indexName: Scalars['String'];
  interval?: InputMaybe<PaymentAnalyticsIntervalEnum>;
  toDate: Scalars['DateTime'];
};

export type QueryPaymentByIdArgs = {
  id: Scalars['ID'];
};

export type QueryPaymentInstantRefundEligibilityArgs = {
  amount: MoneyInput;
  id: Scalars['ID'];
};

export type QueryPaymentLinkByIdArgs = {
  id: Scalars['ID'];
};

export type QueryPaymentLinksArgs = {
  customer?: InputMaybe<CustomerInput>;
  fromDate?: InputMaybe<Scalars['DateTime']>;
  limit?: InputMaybe<Scalars['PositiveInt']>;
  notes?: InputMaybe<Scalars['String']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
  referenceId?: InputMaybe<Scalars['String']>;
  status?: InputMaybe<Array<PaymentLinkStatusEnum>>;
  toDate?: InputMaybe<Scalars['DateTime']>;
};

export type QueryPaymentOverviewArgs = {
  fromDate: Scalars['DateTime'];
  toDate: Scalars['DateTime'];
};

export type QueryPaymentPageByIdArgs = {
  id: Scalars['ID'];
};

export type QueryPaymentPageTransactionsByIdArgs = {
  limit?: InputMaybe<Scalars['PositiveInt']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
  paymentPageId: Scalars['ID'];
};

export type QueryPaymentPagesArgs = {
  limit?: InputMaybe<Scalars['PositiveInt']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
};

export type QueryPaymentSummaryArgs = {
  filterField?: InputMaybe<PaymentSummaryFilterFieldEnum>;
  fromDate: Scalars['DateTime'];
  paymentIndexName: PaymentSummaryIndexEnum;
  paymentsAggregateBy: PaymentAnalyticsAggregateByEnum;
  paymentsAggregationField: PaymentSummaryAggregationFieldEnum;
  refundSpeed?: InputMaybe<SpeedProcessedEnum>;
  toDate: Scalars['DateTime'];
};

export type QueryPaymentsArgs = {
  customer?: InputMaybe<CustomerInput>;
  fromDate?: InputMaybe<Scalars['DateTime']>;
  limit: Scalars['PositiveInt'];
  method?: InputMaybe<PaymentMethodEnum>;
  notes?: InputMaybe<Scalars['String']>;
  offset: Scalars['NonNegativeInt'];
  status?: InputMaybe<Array<PaymentStatusEnum>>;
  toDate?: InputMaybe<Scalars['DateTime']>;
};

export type QueryPayoutBatchByIdArgs = {
  payoutBatchId: Scalars['ID'];
};

export type QueryPayoutBatchesArgs = {
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  pendingOn?: InputMaybe<Scalars['String']>;
  status?: InputMaybe<PayoutBatchStatusEnum>;
};

export type QueryPayoutByIdArgs = {
  id: Scalars['ID'];
};

export type QueryPayoutLinkByIdArgs = {
  id: Scalars['ID'];
};

export type QueryPayoutLinksArgs = {
  contactId?: InputMaybe<Scalars['String']>;
  fromDate?: InputMaybe<Scalars['DateTime']>;
  fundAccountId?: InputMaybe<Scalars['String']>;
  id?: InputMaybe<Scalars['ID']>;
  limit?: InputMaybe<Scalars['PositiveInt']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
  status?: InputMaybe<PayoutLinkStatusEnum>;
  toDate?: InputMaybe<Scalars['DateTime']>;
};

export type QueryPayoutsArgs = {
  contactEmail?: InputMaybe<Scalars['String']>;
  contactId?: InputMaybe<Scalars['String']>;
  contactName?: InputMaybe<Scalars['String']>;
  contactPhone?: InputMaybe<Scalars['String']>;
  contactType?: InputMaybe<Scalars['String']>;
  fromDate?: InputMaybe<Scalars['DateTime']>;
  fundAccountId?: InputMaybe<Scalars['String']>;
  id?: InputMaybe<Scalars['ID']>;
  limit: Scalars['PositiveInt'];
  mode?: InputMaybe<PayoutModeEnum>;
  offset: Scalars['NonNegativeInt'];
  pendingOn?: InputMaybe<PayoutPendingOnInput>;
  pendingOnRoles?: InputMaybe<Array<Scalars['String']>>;
  sourceId?: InputMaybe<Scalars['ID']>;
  status?: InputMaybe<PayoutStatusEnum>;
  toDate?: InputMaybe<Scalars['DateTime']>;
  transactionId?: InputMaybe<Scalars['String']>;
  utr?: InputMaybe<Scalars['String']>;
};

export type QueryPayoutsWorkflowConfigArgs = {
  configType: Scalars['String'];
};

export type QueryQrCodesArgs = {
  fromDate?: InputMaybe<Scalars['DateTime']>;
  limit?: InputMaybe<Scalars['PositiveInt']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
  toDate?: InputMaybe<Scalars['DateTime']>;
};

export type QueryRefundByIdArgs = {
  id: Scalars['ID'];
};

export type QueryRefundsArgs = {
  fromDate?: InputMaybe<Scalars['DateTime']>;
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  paymentId?: InputMaybe<Scalars['ID']>;
  status?: InputMaybe<Array<PaymentRefundStatusEnum>>;
  toDate?: InputMaybe<Scalars['DateTime']>;
};

export type QuerySettlementByIdArgs = {
  id: Scalars['ID'];
};

export type QuerySettlementByUtrArgs = {
  utr: Scalars['String'];
};

export type QuerySettlementsArgs = {
  fromDate?: InputMaybe<Scalars['DateTime']>;
  limit?: InputMaybe<Scalars['PositiveInt']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
  status?: InputMaybe<Array<SettlementStatusEnum>>;
  toDate?: InputMaybe<Scalars['DateTime']>;
};

export type QueryTransactionByIdArgs = {
  id: Scalars['ID'];
};

export type QueryTransactionsArgs = {
  accountNumber: Scalars['String'];
  action?: InputMaybe<TransactionTypeEnum>;
  contactEmail?: InputMaybe<Scalars['String']>;
  contactId?: InputMaybe<Scalars['String']>;
  contactName?: InputMaybe<Scalars['String']>;
  contactPhone?: InputMaybe<Scalars['String']>;
  contactType?: InputMaybe<Scalars['String']>;
  fromDate?: InputMaybe<Scalars['DateTime']>;
  fundAccountId?: InputMaybe<Scalars['String']>;
  limit: Scalars['PositiveInt'];
  mode?: InputMaybe<PayoutModeEnum>;
  offset: Scalars['NonNegativeInt'];
  payoutId?: InputMaybe<Scalars['String']>;
  payoutPurpose?: InputMaybe<Scalars['String']>;
  toDate?: InputMaybe<Scalars['DateTime']>;
  transactionId?: InputMaybe<Scalars['ID']>;
  type?: InputMaybe<TransactionSourceTypeEnum>;
  utr?: InputMaybe<Scalars['String']>;
};

export type QueryUserByIdArgs = {
  id: Scalars['ID'];
};

export type QueryValidateVpaArgs = {
  vpa: Scalars['VPA'];
};

export type QueryVendorPaymentByIdArgs = {
  id: Scalars['ID'];
};

export type QueryVendorPaymentsArgs = {
  contactName?: InputMaybe<Scalars['String']>;
  invoiceNumber?: InputMaybe<Scalars['String']>;
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  sortBy?: InputMaybe<SortByEnum>;
  statuses?: InputMaybe<Array<VendorPaymentStatusEnum>>;
};

export type QueryWhatsappNotificationStatusArgs = {
  source: Scalars['String'];
};

export type RecentTransactionsWidget = {
  __typename?: 'RecentTransactionsWidget';
  title: Scalars['String'];
  type: PaymentsWidgetTypeEnum;
  variant: WidgetVariantEnum;
};

export type RefreshAccessToken = {
  __typename?: 'RefreshAccessToken';
  success: Scalars['Boolean'];
};

export type RefundsResponse = PaginationResponseInterface & {
  __typename?: 'RefundsResponse';
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  refunds: Array<PaymentRefund>;
  total: Scalars['NonNegativeInt'];
};

export type RegisterBusinessResponse = MutationResponseInterface & {
  __typename?: 'RegisterBusinessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type RegisterEmail = RegisterEmailError | RegisterEmailSuccess;

export type RegisterEmailError = MutationResponseInterface & {
  __typename?: 'RegisterEmailError';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type RegisterEmailSuccess = MutationResponseInterface & {
  __typename?: 'RegisterEmailSuccess';
  code: Scalars['PositiveInt'];
  email: Scalars['EmailAddress'];
  merchantId: Scalars['ID'];
  message?: Maybe<Scalars['String']>;
  name: Scalars['String'];
  success: Scalars['Boolean'];
  token?: Maybe<Scalars['String']>;
  userId: Scalars['ID'];
};

export enum RegisterEmailVerificationMethodEnum {
  EMAIL_LINK = 'EMAIL_LINK',
  EMAIL_OTP = 'EMAIL_OTP',
}

export type RegisterEmailVerifyResponse =
  | RegisterEmailVerifyResponseFailure
  | RegisterEmailVerifyResponseSuccess;

export type RegisterEmailVerifyResponseFailure = {
  __typename?: 'RegisterEmailVerifyResponseFailure';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type RegisterEmailVerifyResponseSuccess = {
  __typename?: 'RegisterEmailVerifyResponseSuccess';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  user: User;
};

export type RegisterFcmTokenResponse = MutationResponseInterface & {
  __typename?: 'RegisterFCMTokenResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum RegisterMerchantErrorEnum {
  BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS = 'BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS',
  BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED = 'BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED',
}

export type RegisterMerchantResponse =
  | RegisterMerchantResponseFailure
  | RegisterMerchantResponseSuccess;

export type RegisterMerchantResponseFailure = MutationResponseInterface & {
  __typename?: 'RegisterMerchantResponseFailure';
  code: Scalars['PositiveInt'];
  errorCode?: Maybe<RegisterMerchantErrorEnum>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type RegisterMerchantResponseSuccess = MutationResponseInterface & {
  __typename?: 'RegisterMerchantResponseSuccess';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token: Scalars['String'];
};

export enum RegisterMobileVerifyEnum {
  BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS = 'BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS',
  BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED = 'BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED',
  BAD_REQUEST_SIGNUP_OTP_VERIFICATION_THRESHOLD_EXHAUSTED = 'BAD_REQUEST_SIGNUP_OTP_VERIFICATION_THRESHOLD_EXHAUSTED',
}

export type RegisterMobileVerifyResponse =
  | RegisterMobileVerifyResponseFailure
  | RegisterMobileVerifyResponseSuccess;

export type RegisterMobileVerifyResponseFailure = MutationResponseInterface & {
  __typename?: 'RegisterMobileVerifyResponseFailure';
  code: Scalars['PositiveInt'];
  errorCode?: Maybe<RegisterMobileVerifyEnum>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type RegisterMobileVerifyResponseSuccess = MutationResponseInterface & {
  __typename?: 'RegisterMobileVerifyResponseSuccess';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  user: User;
};

export type RegisterOAuth =
  | AuthUser
  | RegisterOAuthEmailError
  | RegisterOAuthEmailExist
  | RegisterOAuthInvalidTokenError;

export type RegisterOAuthEmailError = {
  __typename?: 'RegisterOAuthEmailError';
  message: Scalars['String'];
};

export type RegisterOAuthEmailExist = {
  __typename?: 'RegisterOAuthEmailExist';
  message: Scalars['String'];
};

export type RegisterOAuthInvalidTokenError = {
  __typename?: 'RegisterOAuthInvalidTokenError';
  message: Scalars['String'];
};

export type RejectPayoutBatchResponse =
  | RejectPayoutBatchResponseFailure
  | RejectPayoutBatchResponseSuccess;

export type RejectPayoutBatchResponseFailure = {
  __typename?: 'RejectPayoutBatchResponseFailure';
  code: Scalars['PositiveInt'];
  failedPayoutBatchIds?: Maybe<Array<Scalars['ID']>>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type RejectPayoutBatchResponseSuccess = {
  __typename?: 'RejectPayoutBatchResponseSuccess';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type RejectPayoutResponse = MutationResponseInterface & {
  __typename?: 'RejectPayoutResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type ResendEmailOtp = MutationResponseInterface & {
  __typename?: 'ResendEmailOtp';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token: Scalars['String'];
};

export type ResendTwoFactorLoginOtpResponse = MutationResponseInterface & {
  __typename?: 'ResendTwoFactorLoginOtpResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type ResetPasswordEmail = {
  __typename?: 'ResetPasswordEmail';
  emailSent: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
};

export type SendApprovePayoutBatchOtp = MutationResponseInterface & {
  __typename?: 'SendApprovePayoutBatchOtp';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token?: Maybe<Scalars['String']>;
};

export type SendApprovePayoutOtp = MutationResponseInterface & {
  __typename?: 'SendApprovePayoutOtp';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token?: Maybe<Scalars['String']>;
};

export type SendCreatePayoutLinkOtp = MutationResponseInterface & {
  __typename?: 'SendCreatePayoutLinkOtp';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token: Scalars['String'];
};

export type SendCreatePayoutOtp = MutationResponseInterface & {
  __typename?: 'SendCreatePayoutOtp';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token: Scalars['String'];
};

export type SendIciciPayoutOtpResponse = MutationResponseInterface & {
  __typename?: 'SendIciciPayoutOtpResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type SendPayoutApproveBulkOtp = MutationResponseInterface & {
  __typename?: 'SendPayoutApproveBulkOtp';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token: Scalars['String'];
};

export type SendPayoutCompositeOtp = MutationResponseInterface & {
  __typename?: 'SendPayoutCompositeOtp';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token?: Maybe<Scalars['String']>;
};

export type Settlement = {
  __typename?: 'Settlement';
  amount: SettlementAmount;
  breakUp?: Maybe<Array<SettlementBreakup>>;
  createdAt: Scalars['DateTime'];
  id: Scalars['ID'];
  status: SettlementStatusEnum;
  transactionSources?: Maybe<Array<Maybe<TransactionSourceDetails>>>;
  /** Unique Transaction Reference number available across banks */
  utr?: Maybe<Scalars['String']>;
};

export type SettlementTransactionSourcesArgs = {
  limit?: InputMaybe<Scalars['PositiveInt']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
  sourceType: SettlementBreakupComponentEnum;
};

export type SettlementAmount = {
  __typename?: 'SettlementAmount';
  fee?: Maybe<Scalars['Float']>;
  settlement: Money;
  tax?: Maybe<Scalars['Float']>;
};

export type SettlementBreakup = {
  __typename?: 'SettlementBreakup';
  amount: Money;
  component?: Maybe<SettlementBreakupComponentEnum>;
  count?: Maybe<Scalars['PositiveInt']>;
  transactionType?: Maybe<SettlementBreakupTransactionTypeEnum>;
};

export enum SettlementBreakupComponentEnum {
  ADJUSTMENT = 'ADJUSTMENT',
  COMMISSION = 'COMMISSION',
  CREDIT_REPAYMENT = 'CREDIT_REPAYMENT',
  DISPUTE = 'DISPUTE',
  FEE = 'FEE',
  FEE_CREDITS = 'FEE_CREDITS',
  FUND_ACCOUNT_VALIDATION = 'FUND_ACCOUNT_VALIDATION',
  PAYMENT = 'PAYMENT',
  PAYMENT_DOMESTIC = 'PAYMENT_DOMESTIC',
  PAYMENT_INTERNATIONAL = 'PAYMENT_INTERNATIONAL',
  PAYOUT = 'PAYOUT',
  REFUND = 'REFUND',
  REFUND_CREDITS = 'REFUND_CREDITS',
  REFUND_DOMESTIC = 'REFUND_DOMESTIC',
  REFUND_INTERNATIONAL = 'REFUND_INTERNATIONAL',
  REVERSAL = 'REVERSAL',
  SETTLEMENTS_ONDEMAND = 'SETTLEMENTS_ONDEMAND',
  SETTLEMENT_TRANSFER = 'SETTLEMENT_TRANSFER',
  TAX = 'TAX',
  TRANSFER = 'TRANSFER',
  TRANSFER_INTERNATIONAL = 'TRANSFER_INTERNATIONAL',
}

export enum SettlementBreakupTransactionTypeEnum {
  CREDIT = 'CREDIT',
  DEBIT = 'DEBIT',
}

export type SettlementCycle = {
  __typename?: 'SettlementCycle';
  accountBalance: Money;
  amountToBeSettled: Money;
  isOnHold?: Maybe<Scalars['Boolean']>;
  nextSettlementOn?: Maybe<Scalars['DateTime']>;
  reason?: Maybe<Scalars['String']>;
};

export enum SettlementStatusEnum {
  CREATED = 'CREATED',
  FAILED = 'FAILED',
  INITIATED = 'INITIATED',
  PROCESSED = 'PROCESSED',
}

export type SettlementsResponse = PaginationResponseInterface & {
  __typename?: 'SettlementsResponse';
  hasMore: Scalars['Boolean'];
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  settlements: Array<Settlement>;
  total: Scalars['NonNegativeInt'];
};

export type SettlementsWidget = {
  __typename?: 'SettlementsWidget';
  description: Scalars['String'];
  title: Scalars['String'];
  type: PaymentsWidgetTypeEnum;
  variant: WidgetVariantEnum;
};

export type SmsNotificationStatusResponse = {
  __typename?: 'SmsNotificationStatusResponse';
  code: Scalars['PositiveInt'];
  isEnabled: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type SmsNotificationToggle = MutationResponseInterface & {
  __typename?: 'SmsNotificationToggle';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum SortByEnum {
  CREATED_DATE = 'CREATED_DATE',
  DUE_DATE = 'DUE_DATE',
}

export enum SpeedProcessedEnum {
  INSTANT = 'INSTANT',
  NORMAL = 'NORMAL',
}

export type TdsCategory = {
  __typename?: 'TDSCategory';
  code: Scalars['String'];
  id: Scalars['Int'];
  name: Scalars['String'];
  rate: Scalars['Float'];
};

export type Transaction = {
  __typename?: 'Transaction';
  amount: Money;
  balance: Money;
  bankingAccountNumber: Scalars['String'];
  createdAt: Scalars['DateTime'];
  id: Scalars['ID'];
  source: TransactionSource;
  type: TransactionTypeEnum;
};

export type TransactionAmount = {
  __typename?: 'TransactionAmount';
  amount: Money;
  amountRefunded: Money;
  amountTransferred: Money;
  baseAmount: Money;
};

export type TransactionError = {
  __typename?: 'TransactionError';
  errorCode?: Maybe<Scalars['String']>;
  errorDescription?: Maybe<Scalars['String']>;
  errorReason?: Maybe<Scalars['String']>;
  errorSource?: Maybe<Scalars['String']>;
  errorStep?: Maybe<Scalars['String']>;
};

export type TransactionSource =
  | Payout
  | TransactionSourceAdjustment
  | TransactionSourceBankTransfer
  | TransactionSourceExternal
  | TransactionSourceFundAccountValidation
  | TransactionSourceReversal;

export type TransactionSourceAdjustment = {
  __typename?: 'TransactionSourceAdjustment';
  description: Scalars['String'];
  id?: Maybe<Scalars['ID']>;
};

export type TransactionSourceBankTransfer = {
  __typename?: 'TransactionSourceBankTransfer';
  id: Scalars['ID'];
  mode: TransactionSourceBankTransferModeEnum;
  payee: TransactionSourceBankTransferPayee;
  payer: TransactionSourceBankTransferPayer;
  reference: Scalars['String'];
};

export enum TransactionSourceBankTransferModeEnum {
  FUND_TRANSFER = 'FUND_TRANSFER',
  IMPS = 'IMPS',
  INTERNAL_FUND_TRANSFER = 'INTERNAL_FUND_TRANSFER',
  NEFT = 'NEFT',
  RTGS = 'RTGS',
  UPI = 'UPI',
}

export type TransactionSourceBankTransferPayee = {
  __typename?: 'TransactionSourceBankTransferPayee';
  accountNumber: Scalars['String'];
};

export type TransactionSourceBankTransferPayer = {
  __typename?: 'TransactionSourceBankTransferPayer';
  accountNumber: Scalars['String'];
  ifsc: Scalars['String'];
  name?: Maybe<Scalars['String']>;
};

export type TransactionSourceDetails = {
  __typename?: 'TransactionSourceDetails';
  amount: Money;
  createdAt?: Maybe<Scalars['DateTime']>;
  fee?: Maybe<Money>;
  id: Scalars['ID'];
  isInternational?: Maybe<Scalars['Boolean']>;
  status?: Maybe<Scalars['String']>;
  tax?: Maybe<Money>;
};

export type TransactionSourceExternal = {
  __typename?: 'TransactionSourceExternal';
  id?: Maybe<Scalars['ID']>;
  utr?: Maybe<Scalars['String']>;
};

export type TransactionSourceFundAccountValidation = {
  __typename?: 'TransactionSourceFundAccountValidation';
  fundAccount?: Maybe<MerchantContactFundAccount>;
  id: Scalars['ID'];
  utr?: Maybe<Scalars['String']>;
};

export type TransactionSourceReversal = {
  __typename?: 'TransactionSourceReversal';
  id: Scalars['ID'];
  payout?: Maybe<Payout>;
  utr?: Maybe<Scalars['String']>;
};

export enum TransactionSourceTypeEnum {
  ADJUSTMENT = 'ADJUSTMENT',
  BANK_ACCOUNT = 'BANK_ACCOUNT',
  EXTERNAL = 'EXTERNAL',
  FUND_ACCOUNT_VALIDATION = 'FUND_ACCOUNT_VALIDATION',
  PAYOUT = 'PAYOUT',
  REVERSAL = 'REVERSAL',
}

export type TransactionStatus = {
  __typename?: 'TransactionStatus';
  captured?: Maybe<Scalars['Boolean']>;
  status?: Maybe<Scalars['String']>;
};

export enum TransactionTypeEnum {
  CREDIT = 'CREDIT',
  DEBIT = 'DEBIT',
}

export type TransactionsResponse = PaginationResponseInterface & {
  __typename?: 'TransactionsResponse';
  hasMore?: Maybe<Scalars['Boolean']>;
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  total?: Maybe<Scalars['NonNegativeInt']>;
  transactions: Array<Transaction>;
};

export enum TwoFactorActionTypeEnum {
  USER_AUTHENTICATION = 'USER_AUTHENTICATION',
  VERIFY_CONTACT = 'VERIFY_CONTACT',
}

export type TwoFactorAddMobileOtpErrorResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorAddMobileOtpErrorResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type TwoFactorAddMobileOtpResponse =
  | TwoFactorAddMobileOtpErrorResponse
  | TwoFactorAddMobileOtpSuccessResponse;

export type TwoFactorAddMobileOtpSuccessResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorAddMobileOtpSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  phone: Phone;
  success: Scalars['Boolean'];
};

export type TwoFactorAddMobileOtpVerifyErrorResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorAddMobileOtpVerifyErrorResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type TwoFactorAddMobileOtpVerifyResponse =
  | TwoFactorAddMobileOtpVerifyErrorResponse
  | TwoFactorAddMobileOtpVerifySuccessResponse;

export type TwoFactorAddMobileOtpVerifySuccessResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorAddMobileOtpVerifySuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  user: User;
};

export type TwoFactorAuthUpdateFailureResponse = {
  __typename?: 'TwoFactorAuthUpdateFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type TwoFactorAuthUpdateResponse =
  | TwoFactorAuthUpdateFailureResponse
  | TwoFactorAuthUpdateSuccessResponse;

export type TwoFactorAuthUpdateSuccessResponse = {
  __typename?: 'TwoFactorAuthUpdateSuccessResponse';
  code: Scalars['PositiveInt'];
  isTwoFactorEnabled: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type TwoFactorEmailOtpVerifyFailureResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorEmailOtpVerifyFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type TwoFactorEmailOtpVerifyResponse =
  | TwoFactorEmailOtpVerifyFailureResponse
  | TwoFactorEmailOtpVerifySuccessResponse;

export type TwoFactorEmailOtpVerifySuccessResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorEmailOtpVerifySuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token: Scalars['String'];
};

export type TwoFactorOtpFailureResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorOtpFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum TwoFactorOtpMediumEnum {
  EMAIL = 'EMAIL',
  SMS = 'SMS',
}

export type TwoFactorOtpResponse = TwoFactorOtpFailureResponse | TwoFactorOtpSuccessResponse;

export type TwoFactorOtpSuccessResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorOtpSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  token: Scalars['String'];
};

export type TwoFactorPasswordCreateErrorResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorPasswordCreateErrorResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type TwoFactorPasswordCreateResponse =
  | TwoFactorPasswordCreateErrorResponse
  | TwoFactorPasswordCreateSuccessResponse;

export type TwoFactorPasswordCreateSuccessResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorPasswordCreateSuccessResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  user: User;
};

export type TwoFactorPasswordEnabledErrorResponse = {
  __typename?: 'TwoFactorPasswordEnabledErrorResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type TwoFactorPasswordEnabledResponse =
  | TwoFactorPasswordEnabledErrorResponse
  | TwoFactorPasswordEnabledSuccessResponse;

export type TwoFactorPasswordEnabledSuccessResponse = {
  __typename?: 'TwoFactorPasswordEnabledSuccessResponse';
  code: Scalars['PositiveInt'];
  isPasswordEnabled: Scalars['Boolean'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type TwoFactorUnverifiedMobileVerifyResponse = MutationResponseInterface & {
  __typename?: 'TwoFactorUnverifiedMobileVerifyResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type UpdateMerchantConsentResponse = MutationResponseInterface & {
  __typename?: 'UpdateMerchantConsentResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export enum UpiTerminalProcurementStatusEnum {
  NO_BANNER = 'NO_BANNER',
  PENDING = 'PENDING',
  PENDING_ACK = 'PENDING_ACK',
  PENDING_SEEN = 'PENDING_SEEN',
  REJECTED = 'REJECTED',
  SUCCESS = 'SUCCESS',
}

export type User = {
  __typename?: 'User';
  email?: Maybe<Scalars['EmailAddress']>;
  id: Scalars['ID'];
  isAccountLocked: Scalars['Boolean'];
  isAccountVerified: Scalars['Boolean'];
  isContactNumberVerified: Scalars['Boolean'];
  isEmailVerified: Scalars['Boolean'];
  isSignUpViaEmail: Scalars['Boolean'];
  isTwoFactorEnabled: Scalars['Boolean'];
  isTwoFactorEnforced: Scalars['Boolean'];
  /** @deprecated Use user.roles.merchant */
  merchants: Array<Merchant>;
  name?: Maybe<Scalars['String']>;
  phone?: Maybe<Phone>;
  roles: Array<UserRole>;
  signupCampaign?: Maybe<UserSignupCampaignEnum>;
};

export type UserRolesArgs = {
  merchantId?: InputMaybe<Scalars['ID']>;
};

export enum UserAcquisitionSourceEnum {
  ANDROID = 'ANDROID',
  DWEB = 'DWEB',
  IOS = 'IOS',
  MWEB = 'MWEB',
}

export type UserAuthentication = {
  __typename?: 'UserAuthentication';
  isAuthenticated: Scalars['Boolean'];
  merchantId?: Maybe<Scalars['String']>;
  userId?: Maybe<Scalars['String']>;
};

export type UserContactDetails = {
  __typename?: 'UserContactDetails';
  contact?: Maybe<Scalars['String']>;
  email?: Maybe<Scalars['String']>;
};

export type UserContactDetailsUpdateResponse = MutationResponseInterface & {
  __typename?: 'UserContactDetailsUpdateResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type UserDeviceAnalyticsResponse = MutationResponseInterface & {
  __typename?: 'UserDeviceAnalyticsResponse';
  code: Scalars['PositiveInt'];
  message: Scalars['String'];
  success: Scalars['Boolean'];
};

export type UserLogout = {
  __typename?: 'UserLogout';
  isLoggedOut: Scalars['Boolean'];
};

export enum UserOtpVerifyErrorTypeEnum {
  EMPTY_RESPONSE = 'EMPTY_RESPONSE',
  INVALID_OTP = 'INVALID_OTP',
  INVALID_RESPONSE = 'INVALID_RESPONSE',
  LOGIN_UNAUTHENTICATED = 'LOGIN_UNAUTHENTICATED',
  VALID_RESPONSE = 'VALID_RESPONSE',
}

export type UserOtpVerifyResponse = MutationResponseInterface & {
  __typename?: 'UserOtpVerifyResponse';
  code: Scalars['PositiveInt'];
  errorCode?: Maybe<UserOtpVerifyErrorTypeEnum>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type UserRole = {
  __typename?: 'UserRole';
  banking?: Maybe<UserRoleBankingEnum>;
  bankingPermissions: Array<Maybe<Scalars['String']>>;
  bankingRole?: Maybe<MerchantBankingRole>;
  merchant: Merchant;
  payments?: Maybe<UserRolePaymentsEnum>;
};

export enum UserRoleBankingEnum {
  ADMIN = 'ADMIN',
  CHARTERED_ACCOUNTANT = 'CHARTERED_ACCOUNTANT',
  FINANCE_L1 = 'FINANCE_L1',
  FINANCE_L2 = 'FINANCE_L2',
  FINANCE_L3 = 'FINANCE_L3',
  OPERATIONS = 'OPERATIONS',
  OWNER = 'OWNER',
  VENDOR = 'VENDOR',
  VIEW_ONLY = 'VIEW_ONLY',
}

export enum UserRolePaymentsEnum {
  ADMIN = 'ADMIN',
  AUTH_LINK_AGENT = 'AUTH_LINK_AGENT',
  AUTH_LINK_SUPERVISOR = 'AUTH_LINK_SUPERVISOR',
  FINANCE = 'FINANCE',
  LINKED_ACCOUNT_ADMIN = 'LINKED_ACCOUNT_ADMIN',
  LINKED_ACCOUNT_OWNER = 'LINKED_ACCOUNT_OWNER',
  MANAGER = 'MANAGER',
  OPERATIONS = 'OPERATIONS',
  OWNER = 'OWNER',
  RBL_AGENT = 'RBL_AGENT',
  RBL_SUPERVISOR = 'RBL_SUPERVISOR',
  SELLER_APP = 'SELLER_APP',
  SUPPORT = 'SUPPORT',
  VIEW_ONLY = 'VIEW_ONLY',
  PARTNER = 'PARTNER',
  PARTNER_AGENT = 'PARTNER_AGENT',
}

export enum UserSignupCampaignEnum {
  EASY_ONBOARDING = 'EASY_ONBOARDING',
  I18N_MY_SIGNUP = 'I18N_MY_SIGNUP',
  /** @deprecated use I18N_MY_SIGNUP instead, this is deprecated */
  INTERNATIONAL = 'INTERNATIONAL',
  P2PM_ONBOARDING = 'P2PM_ONBOARDING',
  PHANTOM_ONBOARDING = 'PHANTOM_ONBOARDING',
}

export type ValidateVpaFailureResponse = {
  __typename?: 'ValidateVpaFailureResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type ValidateVpaResponse = ValidateVpaFailureResponse | ValidateVpaSuccessResponse;

export type ValidateVpaSuccessResponse = {
  __typename?: 'ValidateVpaSuccessResponse';
  code: Scalars['PositiveInt'];
  customerName: Scalars['String'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  vpa: Scalars['String'];
};

export type VendorPayment = {
  __typename?: 'VendorPayment';
  cancelledBy?: Maybe<User>;
  createdBy: User;
  dates: VendorPaymentDates;
  description?: Maybe<Scalars['String']>;
  fundAccount?: Maybe<MerchantContactFundAccount>;
  gst?: Maybe<VendorPaymentGst>;
  id: Scalars['ID'];
  invoice: VendorPaymentInvoice;
  merchantContact?: Maybe<MerchantContact>;
  notes?: Maybe<Scalars['JSONObject']>;
  payoutAmounts?: Maybe<VendorPaymentPayoutAmounts>;
  payouts: Array<Payout>;
  status: VendorPaymentStatusEnum;
  subtotal?: Maybe<Money>;
  tds?: Maybe<VendorPaymentTds>;
  total?: Maybe<Money>;
};

export type VendorPaymentPayoutsArgs = {
  limit?: InputMaybe<Scalars['PositiveInt']>;
  offset?: InputMaybe<Scalars['NonNegativeInt']>;
};

export type VendorPaymentCancelResponse = MutationResponseInterface & {
  __typename?: 'VendorPaymentCancelResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type VendorPaymentDates = {
  __typename?: 'VendorPaymentDates';
  cancelledAt?: Maybe<Scalars['DateTime']>;
  createdAt: Scalars['DateTime'];
  draftCreatedAt?: Maybe<Scalars['DateTime']>;
  dueOn?: Maybe<Scalars['DateTime']>;
  invoiceIssuedAt: Scalars['DateTime'];
  paidAt?: Maybe<Scalars['DateTime']>;
  unpaidAt?: Maybe<Scalars['DateTime']>;
  updatedAt?: Maybe<Scalars['DateTime']>;
};

export type VendorPaymentGst = {
  __typename?: 'VendorPaymentGST';
  amount: Money;
  gstin?: Maybe<Scalars['String']>;
  type: GstTypeEnum;
};

export type VendorPaymentInvoice = {
  __typename?: 'VendorPaymentInvoice';
  invoiceAttachment?: Maybe<VendorPaymentInvoiceAttachment>;
  invoiceNumber?: Maybe<Scalars['String']>;
};

export type VendorPaymentInvoiceAttachment = {
  __typename?: 'VendorPaymentInvoiceAttachment';
  fileId: Scalars['String'];
  mime: Scalars['String'];
  name: Scalars['String'];
  signedUrl: Scalars['String'];
};

export type VendorPaymentPayoutAmounts = {
  __typename?: 'VendorPaymentPayoutAmounts';
  paidAmount?: Maybe<Money>;
  pendingAmount?: Maybe<Money>;
  processingAmount?: Maybe<Money>;
  scheduledAmount?: Maybe<Money>;
};

export type VendorPaymentPayoutCreateResponse = MutationResponseInterface & {
  __typename?: 'VendorPaymentPayoutCreateResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
  vendorPayment?: Maybe<VendorPayment>;
};

export enum VendorPaymentStatusEnum {
  CANCELLED = 'CANCELLED',
  DRAFT = 'DRAFT',
  IN_APPROVAL = 'IN_APPROVAL',
  PAID = 'PAID',
  PARTIALLY_PAID = 'PARTIALLY_PAID',
  PROCESSING = 'PROCESSING',
  REJECTED = 'REJECTED',
  SCHEDULED = 'SCHEDULED',
  UNPAID = 'UNPAID',
}

export type VendorPaymentTds = {
  __typename?: 'VendorPaymentTDS';
  amount: Money;
  deductedAmount: Money;
  tdsCategory: TdsCategory;
};

export type VendorPaymentsResponse = PaginationResponseInterface & {
  __typename?: 'VendorPaymentsResponse';
  hasMore?: Maybe<Scalars['Boolean']>;
  limit: Scalars['PositiveInt'];
  offset: Scalars['NonNegativeInt'];
  total?: Maybe<Scalars['NonNegativeInt']>;
  vendorPayments: Array<VendorPayment>;
};

export type WhatsappNotificationStatusResponse = {
  __typename?: 'WhatsappNotificationStatusResponse';
  code: Scalars['PositiveInt'];
  isEnabled?: Maybe<Scalars['Boolean']>;
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type WhatsappNotificationToggle = MutationResponseInterface & {
  __typename?: 'WhatsappNotificationToggle';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type Widget =
  | AcceptPaymentsWidget
  | OnboardingWidget
  | PaymentAnalyticsWidget
  | PaymentHandleWidget
  | PaymentsWidgetError
  | RecentTransactionsWidget
  | SettlementsWidget;

export enum WidgetVariantEnum {
  VARIANT_A = 'VARIANT_A',
}

export type Workflow = {
  __typename?: 'Workflow';
  config: WorkflowConfig;
  creator: WorkflowCreator;
  id: Scalars['ID'];
  states?: Maybe<Array<WorkflowState>>;
  status: WorkflowStatusEnum;
};

export type WorkflowConfig = {
  __typename?: 'WorkflowConfig';
  createdAt: Scalars['DateTime'];
  enabled: Scalars['Boolean'];
  id: Scalars['ID'];
  name: Scalars['String'];
  template: WorkflowConfigTemplate;
  type: Scalars['String'];
};

export type WorkflowConfigState = {
  __typename?: 'WorkflowConfigState';
  name: Scalars['String'];
  rule: WorkflowConfigStateRulePayout;
  transition: WorkflowConfigStateTransition;
  type?: Maybe<WorkflowConfigStateTypeEnum>;
};

export type WorkflowConfigStateRulePayout =
  | WorkflowConfigStateRulePayoutTypeBetween
  | WorkflowConfigStateRulePayoutTypeChecker
  | WorkflowConfigStateRulePayoutTypeMergeStates;

export type WorkflowConfigStateRulePayoutTypeBetween = {
  __typename?: 'WorkflowConfigStateRulePayoutTypeBetween';
  key: Scalars['String'];
  max: Scalars['PositiveInt'];
  min: Scalars['PositiveInt'];
};

export type WorkflowConfigStateRulePayoutTypeChecker = {
  __typename?: 'WorkflowConfigStateRulePayoutTypeChecker';
  count: Scalars['PositiveInt'];
  key: Scalars['String'];
  value: Scalars['String'];
};

export type WorkflowConfigStateRulePayoutTypeMergeStates = {
  __typename?: 'WorkflowConfigStateRulePayoutTypeMergeStates';
  states: Array<Scalars['String']>;
};

export type WorkflowConfigStateTransition = {
  __typename?: 'WorkflowConfigStateTransition';
  isEndState: Scalars['Boolean'];
  isStartState: Scalars['Boolean'];
  nextStates: Array<Scalars['String']>;
};

export enum WorkflowConfigStateTypeEnum {
  BETWEEN = 'BETWEEN',
  CHECKER = 'CHECKER',
  MERGE_STATES = 'MERGE_STATES',
}

export type WorkflowConfigTemplate = {
  __typename?: 'WorkflowConfigTemplate';
  states: Array<WorkflowConfigState>;
  type: WorkflowConfigTemplateTypeEnum;
};

export enum WorkflowConfigTemplateTypeEnum {
  APPROVAL = 'APPROVAL',
}

export type WorkflowCreator = {
  __typename?: 'WorkflowCreator';
  id: Scalars['ID'];
  type: WorkflowCreatorTypeEnum;
};

export enum WorkflowCreatorTypeEnum {
  MERCHANT = 'MERCHANT',
  USER = 'USER',
}

export type WorkflowState = {
  __typename?: 'WorkflowState';
  actions?: Maybe<Array<WorkflowStateAction>>;
  dates: WorkflowStateDates;
  id: Scalars['ID'];
  name: Scalars['String'];
  rule: WorkflowConfigStateRulePayout;
  status: WorkflowStateStatusEnum;
  type: WorkflowConfigStateTypeEnum;
};

export type WorkflowStateAction = {
  __typename?: 'WorkflowStateAction';
  actor: WorkflowStateActionActor;
  createdAt: Scalars['DateTime'];
  id: Scalars['ID'];
  status: WorkflowStateActionStatusEnum;
  type: Scalars['String'];
};

export type WorkflowStateActionActor = {
  __typename?: 'WorkflowStateActionActor';
  comment?: Maybe<Scalars['String']>;
  id: Scalars['ID'];
  key: Scalars['String'];
  meta?: Maybe<Scalars['JSONObject']>;
  type: Scalars['String'];
  value: Scalars['String'];
};

export enum WorkflowStateActionStatusEnum {
  CREATED = 'CREATED',
  FAILED = 'FAILED',
  INITIATED = 'INITIATED',
  INIT_FAILED = 'INIT_FAILED',
  PROCESSED = 'PROCESSED',
}

export type WorkflowStateDates = {
  __typename?: 'WorkflowStateDates';
  createdAt: Scalars['DateTime'];
  updatedAt: Scalars['DateTime'];
};

export enum WorkflowStateStatusEnum {
  CREATED = 'CREATED',
  PENDING_ACTION = 'PENDING_ACTION',
  PROCESSED = 'PROCESSED',
}

export enum WorkflowStatusEnum {
  CREATED = 'CREATED',
  FAILED = 'FAILED',
  INITIATED = 'INITIATED',
  INIT_FAILED = 'INIT_FAILED',
  PROCESSED = 'PROCESSED',
}

export type MerchantConfigurationUpdateResponse = MutationResponseInterface & {
  __typename?: 'merchantConfigurationUpdateResponse';
  code: Scalars['PositiveInt'];
  message: Scalars['String'];
  success: Scalars['Boolean'];
};

export type UserOtpResponse = {
  __typename?: 'userOtpResponse';
  code: Scalars['PositiveInt'];
  message?: Maybe<Scalars['String']>;
  success: Scalars['Boolean'];
};

export type ResolverTypeWrapper<T> = Promise<T> | T;

export type ResolverWithResolve<TResult, TParent, TContext, TArgs> = {
  resolve: ResolverFn<TResult, TParent, TContext, TArgs>;
};
export type Resolver<TResult, TParent = {}, TContext = {}, TArgs = {}> =
  | ResolverFn<TResult, TParent, TContext, TArgs>
  | ResolverWithResolve<TResult, TParent, TContext, TArgs>;

export type ResolverFn<TResult, TParent, TContext, TArgs> = (
  parent: TParent,
  args: TArgs,
  context: TContext,
  info: GraphQLResolveInfo,
) => Promise<TResult> | TResult;

export type SubscriptionSubscribeFn<TResult, TParent, TContext, TArgs> = (
  parent: TParent,
  args: TArgs,
  context: TContext,
  info: GraphQLResolveInfo,
) => AsyncIterable<TResult> | Promise<AsyncIterable<TResult>>;

export type SubscriptionResolveFn<TResult, TParent, TContext, TArgs> = (
  parent: TParent,
  args: TArgs,
  context: TContext,
  info: GraphQLResolveInfo,
) => TResult | Promise<TResult>;

export interface SubscriptionSubscriberObject<
  TResult,
  TKey extends string,
  TParent,
  TContext,
  TArgs,
> {
  subscribe: SubscriptionSubscribeFn<{ [key in TKey]: TResult }, TParent, TContext, TArgs>;
  resolve?: SubscriptionResolveFn<TResult, { [key in TKey]: TResult }, TContext, TArgs>;
}

export interface SubscriptionResolverObject<TResult, TParent, TContext, TArgs> {
  subscribe: SubscriptionSubscribeFn<any, TParent, TContext, TArgs>;
  resolve: SubscriptionResolveFn<TResult, any, TContext, TArgs>;
}

export type SubscriptionObject<TResult, TKey extends string, TParent, TContext, TArgs> =
  | SubscriptionSubscriberObject<TResult, TKey, TParent, TContext, TArgs>
  | SubscriptionResolverObject<TResult, TParent, TContext, TArgs>;

export type SubscriptionResolver<
  TResult,
  TKey extends string,
  TParent = {},
  TContext = {},
  TArgs = {},
> =
  | ((...args: any[]) => SubscriptionObject<TResult, TKey, TParent, TContext, TArgs>)
  | SubscriptionObject<TResult, TKey, TParent, TContext, TArgs>;

export type TypeResolveFn<TTypes, TParent = {}, TContext = {}> = (
  parent: TParent,
  context: TContext,
  info: GraphQLResolveInfo,
) => Maybe<TTypes> | Promise<Maybe<TTypes>>;

export type IsTypeOfResolverFn<T = {}, TContext = {}> = (
  obj: T,
  context: TContext,
  info: GraphQLResolveInfo,
) => boolean | Promise<boolean>;

export type NextResolverFn<T> = () => Promise<T>;

export type DirectiveResolverFn<TResult = {}, TParent = {}, TContext = {}, TArgs = {}> = (
  next: NextResolverFn<TResult>,
  parent: TParent,
  args: TArgs,
  context: TContext,
  info: GraphQLResolveInfo,
) => TResult | Promise<TResult>;

/** Mapping between all available schema types and the resolvers types */
export type ResolversTypes = {
  AadhaarCaptchaErrorTypeEnum: AadhaarCaptchaErrorTypeEnum;
  AadhaarCaptchaResponse: ResolverTypeWrapper<AadhaarCaptchaResponse>;
  AadhaarCaptchaV2FailureResponse: ResolverTypeWrapper<AadhaarCaptchaV2FailureResponse>;
  AadhaarCaptchaV2Response:
    | ResolversTypes['AadhaarCaptchaV2FailureResponse']
    | ResolversTypes['AadhaarCaptchaV2SuccessResponse'];
  AadhaarCaptchaV2SuccessResponse: ResolverTypeWrapper<AadhaarCaptchaV2SuccessResponse>;
  AadhaarCaptchaVerifyErrorTypeEnum: AadhaarCaptchaVerifyErrorTypeEnum;
  AadhaarCaptchaVerifyResponse: ResolverTypeWrapper<AadhaarCaptchaVerifyResponse>;
  AadhaarDigilockerOtpFailureResponse: ResolverTypeWrapper<AadhaarDigilockerOtpFailureResponse>;
  AadhaarDigilockerOtpResponse:
    | ResolversTypes['AadhaarDigilockerOtpFailureResponse']
    | ResolversTypes['AadhaarDigilockerOtpSuccessResponse'];
  AadhaarDigilockerOtpSuccessResponse: ResolverTypeWrapper<AadhaarDigilockerOtpSuccessResponse>;
  AadhaarDigilockerOtpVerifyFailureResponse: ResolverTypeWrapper<AadhaarDigilockerOtpVerifyFailureResponse>;
  AadhaarDigilockerOtpVerifyResponse:
    | ResolversTypes['AadhaarDigilockerOtpVerifyFailureResponse']
    | ResolversTypes['AadhaarDigilockerOtpVerifySuccessResponse'];
  AadhaarDigilockerOtpVerifySuccessResponse: ResolverTypeWrapper<AadhaarDigilockerOtpVerifySuccessResponse>;
  AadhaarDigilockerRedirectionUrlErrorTypeEnum: AadhaarDigilockerRedirectionUrlErrorTypeEnum;
  AadhaarDigilockerRedirectionUrlFailureResponse: ResolverTypeWrapper<AadhaarDigilockerRedirectionUrlFailureResponse>;
  AadhaarDigilockerRedirectionUrlResponse:
    | ResolversTypes['AadhaarDigilockerRedirectionUrlFailureResponse']
    | ResolversTypes['AadhaarDigilockerRedirectionUrlSuccessResponse'];
  AadhaarDigilockerRedirectionUrlSuccessResponse: ResolverTypeWrapper<AadhaarDigilockerRedirectionUrlSuccessResponse>;
  AadhaarDigilockerRedirectionUrlVerifyResponse:
    | ResolversTypes['AadhaarDigilockerRedirectionVerificationFailureResponse']
    | ResolversTypes['AadhaarDigilockerRedirectionVerificationSuccessResponse'];
  AadhaarDigilockerRedirectionVerificationErrorTypeEnum: AadhaarDigilockerRedirectionVerificationErrorTypeEnum;
  AadhaarDigilockerRedirectionVerificationFailureResponse: ResolverTypeWrapper<AadhaarDigilockerRedirectionVerificationFailureResponse>;
  AadhaarDigilockerRedirectionVerificationSuccessResponse: ResolverTypeWrapper<AadhaarDigilockerRedirectionVerificationSuccessResponse>;
  AadhaarOtpDigilockerErrorEnum: AadhaarOtpDigilockerErrorEnum;
  AadhaarOtpVerifyErrorTypeEnum: AadhaarOtpVerifyErrorTypeEnum;
  AadhaarOtpVerifyResponse: ResolverTypeWrapper<AadhaarOtpVerifyResponse>;
  AcceptPaymentsProduct: ResolverTypeWrapper<AcceptPaymentsProduct>;
  AcceptPaymentsWidget: ResolverTypeWrapper<AcceptPaymentsWidget>;
  AccountVerificationOtpResendResponse: ResolverTypeWrapper<AccountVerificationOtpResendResponse>;
  AccountVerificationOtpResponse: ResolverTypeWrapper<AccountVerificationOtpResponse>;
  AcquirerData: ResolverTypeWrapper<AcquirerData>;
  Address: ResolverTypeWrapper<Address>;
  AddressByPincodeFailureResponse: ResolverTypeWrapper<AddressByPincodeFailureResponse>;
  AddressByPincodeResponse:
    | ResolversTypes['AddressByPincodeFailureResponse']
    | ResolversTypes['AddressByPincodeSuccessResponse'];
  AddressByPincodeSuccessResponse: ResolverTypeWrapper<AddressByPincodeSuccessResponse>;
  AggregationResultType: ResolverTypeWrapper<AggregationResultType>;
  ApiKeyRegenerationDelayTypeEnum: ApiKeyRegenerationDelayTypeEnum;
  ApproveIciciPayoutResponse: ResolverTypeWrapper<ApproveIciciPayoutResponse>;
  ApprovePayoutBatchResponse:
    | ResolversTypes['ApprovePayoutBatchResponseFailure']
    | ResolversTypes['ApprovePayoutBatchResponseSuccess'];
  ApprovePayoutBatchResponseFailure: ResolverTypeWrapper<ApprovePayoutBatchResponseFailure>;
  ApprovePayoutBatchResponseSuccess: ResolverTypeWrapper<ApprovePayoutBatchResponseSuccess>;
  ApprovePayoutResponse: ResolverTypeWrapper<ApprovePayoutResponse>;
  Auth:
    | ResolversTypes['AuthUnauthenticated']
    | ResolversTypes['AuthUnregistered']
    | ResolversTypes['AuthUser'];
  AuthErrorCodeEnum: AuthErrorCodeEnum;
  AuthSourceEnum: AuthSourceEnum;
  AuthUnauthenticated: ResolverTypeWrapper<AuthUnauthenticated>;
  AuthUnregistered: ResolverTypeWrapper<AuthUnregistered>;
  AuthUser: ResolverTypeWrapper<AuthUser>;
  Bank: ResolverTypeWrapper<Bank>;
  BigInt: ResolverTypeWrapper<Scalars['BigInt']>;
  Boolean: ResolverTypeWrapper<Scalars['Boolean']>;
  BusinessType: ResolverTypeWrapper<BusinessType>;
  CaptchaModeEnum: CaptchaModeEnum;
  CheckoutOptions: ResolverTypeWrapper<CheckoutOptions>;
  ClarificationComment: ResolverTypeWrapper<ClarificationComment>;
  ClarificationComments: ResolverTypeWrapper<ClarificationComments>;
  ClientPlatformEnum: ClientPlatformEnum;
  ConfigData: ResolverTypeWrapper<ConfigData>;
  CouponApplyResponse: ResolverTypeWrapper<CouponApplyResponse>;
  CouponValidateResponse: ResolverTypeWrapper<CouponValidateResponse>;
  Currency: ResolverTypeWrapper<Currency>;
  CurrencyCodeEnum: CurrencyCodeEnum;
  CurrencyInput: CurrencyInput;
  CurrencyNameEnum: CurrencyNameEnum;
  Customer: ResolverTypeWrapper<Customer>;
  CustomerAddress: ResolverTypeWrapper<CustomerAddress>;
  CustomerInput: CustomerInput;
  DateTime: ResolverTypeWrapper<Scalars['DateTime']>;
  DeregisterFCMTokenResponse: ResolverTypeWrapper<DeregisterFcmTokenResponse>;
  DeviceAnalyticsDataInput: DeviceAnalyticsDataInput;
  DigilockerVerificationTypeEnum: DigilockerVerificationTypeEnum;
  EmailAddress: ResolverTypeWrapper<Scalars['EmailAddress']>;
  FailedPaymentsOverviewFailureResponse: ResolverTypeWrapper<FailedPaymentsOverviewFailureResponse>;
  FailedPaymentsOverviewResponse:
    | ResolversTypes['FailedPaymentsOverviewFailureResponse']
    | ResolversTypes['FailedPaymentsOverviewSuccessResponse'];
  FailedPaymentsOverviewSuccessResponse: ResolverTypeWrapper<FailedPaymentsOverviewSuccessResponse>;
  FeeBasedGatingPaymentStatusEnum: FeeBasedGatingPaymentStatusEnum;
  FeeBearerEnum: FeeBearerEnum;
  FieldDetailsInput: FieldDetailsInput;
  Float: ResolverTypeWrapper<Scalars['Float']>;
  GoalTrackerMetaData: ResolverTypeWrapper<GoalTrackerMetaData>;
  GoalTrackerSettings: ResolverTypeWrapper<GoalTrackerSettings>;
  GstTypeEnum: GstTypeEnum;
  ID: ResolverTypeWrapper<Scalars['ID']>;
  Image: ResolverTypeWrapper<Image>;
  Int: ResolverTypeWrapper<Scalars['Int']>;
  Invoice: ResolverTypeWrapper<Invoice>;
  InvoiceAmount: ResolverTypeWrapper<InvoiceAmount>;
  InvoiceDate: ResolverTypeWrapper<InvoiceDate>;
  InvoiceItem: ResolverTypeWrapper<InvoiceItem>;
  InvoiceSmsStatusEnum: InvoiceSmsStatusEnum;
  InvoiceStatusEnum: InvoiceStatusEnum;
  InvoiceTypeEnum: InvoiceTypeEnum;
  InvoicesResponse: ResolverTypeWrapper<InvoicesResponse>;
  JSON: ResolverTypeWrapper<Scalars['JSON']>;
  JSONObject: ResolverTypeWrapper<Scalars['JSONObject']>;
  LoginOtpError: ResolverTypeWrapper<LoginOtpError>;
  LoginOtpErrorCodeEnum: LoginOtpErrorCodeEnum;
  LoginOtpResendError: ResolverTypeWrapper<LoginOtpResendError>;
  LoginOtpResendResponse:
    | ResolversTypes['LoginOtpResendError']
    | ResolversTypes['LoginOtpResendSuccess'];
  LoginOtpResendSuccess: ResolverTypeWrapper<LoginOtpResendSuccess>;
  LoginOtpResponse: ResolversTypes['LoginOtpError'] | ResolversTypes['LoginOtpSuccess'];
  LoginOtpSuccess: ResolverTypeWrapper<LoginOtpSuccess>;
  Merchant: ResolverTypeWrapper<Merchant>;
  MerchantAcceptanceChannel: ResolverTypeWrapper<MerchantAcceptanceChannel>;
  MerchantAcceptanceChannelInput: MerchantAcceptanceChannelInput;
  MerchantAcceptanceChannelWhatsappSmsEmail: ResolverTypeWrapper<MerchantAcceptanceChannelWhatsappSmsEmail>;
  MerchantAcceptanceChannelWhatsappSmsEmailInput: MerchantAcceptanceChannelWhatsappSmsEmailInput;
  MerchantActivation: ResolverTypeWrapper<
    Omit<MerchantActivation, 'merchantEscalations'> & {
      merchantEscalations: ResolversTypes['MerchantEscalations'];
    }
  >;
  MerchantActivationDedupe: ResolverTypeWrapper<MerchantActivationDedupe>;
  MerchantActivationEscalationsBreached: ResolverTypeWrapper<MerchantActivationEscalationsBreached>;
  MerchantActivationEscalationsNotBreached: ResolverTypeWrapper<MerchantActivationEscalationsNotBreached>;
  MerchantActivationFlow: ResolverTypeWrapper<MerchantActivationFlow>;
  MerchantActivationFlowEnum: MerchantActivationFlowEnum;
  MerchantActivationMilestoneEnum: MerchantActivationMilestoneEnum;
  MerchantActivationResponse: ResolverTypeWrapper<MerchantActivationResponse>;
  MerchantActivationStatusEnum: MerchantActivationStatusEnum;
  MerchantAddress: ResolverTypeWrapper<MerchantAddress>;
  MerchantAddressInput: MerchantAddressInput;
  MerchantAnalytics: ResolverTypeWrapper<MerchantAnalytics>;
  MerchantApiKey: ResolverTypeWrapper<MerchantApiKey>;
  MerchantApiKeyCreateResponse: ResolverTypeWrapper<MerchantApiKeyCreateResponse>;
  MerchantApiKeyInterface:
    | ResolversTypes['MerchantApiKey']
    | ResolversTypes['MerchantApiKeyCreateResponse']
    | ResolversTypes['MerchantApiKeyRegenerateNew']
    | ResolversTypes['MerchantApiKeyRegenerateOld']
    | ResolversTypes['MerchantApiKeysCreateSuccess'];
  MerchantApiKeyRegenerateNew: ResolverTypeWrapper<MerchantApiKeyRegenerateNew>;
  MerchantApiKeyRegenerateOld: ResolverTypeWrapper<MerchantApiKeyRegenerateOld>;
  MerchantApiKeyRegenerateResponse: ResolverTypeWrapper<MerchantApiKeyRegenerateResponse>;
  MerchantApiKeysCreateFailure: ResolverTypeWrapper<MerchantApiKeysCreateFailure>;
  MerchantApiKeysCreateResponse:
    | ResolversTypes['MerchantApiKeysCreateFailure']
    | ResolversTypes['MerchantApiKeysCreateSuccess'];
  MerchantApiKeysCreateSuccess: ResolverTypeWrapper<MerchantApiKeysCreateSuccess>;
  MerchantAverageOrderField: ResolverTypeWrapper<MerchantAverageOrderField>;
  MerchantAverageOrderFieldValue: ResolverTypeWrapper<MerchantAverageOrderFieldValue>;
  MerchantBalance: ResolverTypeWrapper<MerchantBalance>;
  MerchantBalanceAccountTypeEnum: MerchantBalanceAccountTypeEnum;
  MerchantBalanceProductTypeEnum: MerchantBalanceProductTypeEnum;
  MerchantBank: ResolverTypeWrapper<MerchantBank>;
  MerchantBankAccountDetails: ResolverTypeWrapper<MerchantBankAccountDetails>;
  MerchantBankAccountDocumentUploadFailureResponse: ResolverTypeWrapper<MerchantBankAccountDocumentUploadFailureResponse>;
  MerchantBankAccountDocumentUploadResponse:
    | ResolversTypes['MerchantBankAccountDocumentUploadFailureResponse']
    | ResolversTypes['MerchantBankAccountDocumentUploadSuccessResponse'];
  MerchantBankAccountDocumentUploadSuccessResponse: ResolverTypeWrapper<MerchantBankAccountDocumentUploadSuccessResponse>;
  MerchantBankAccountUpdateFailureResponse: ResolverTypeWrapper<MerchantBankAccountUpdateFailureResponse>;
  MerchantBankAccountUpdateResponse:
    | ResolversTypes['MerchantBankAccountUpdateFailureResponse']
    | ResolversTypes['MerchantBankAccountUpdateSuccessResponse'];
  MerchantBankAccountUpdateSuccessResponse: ResolverTypeWrapper<MerchantBankAccountUpdateSuccessResponse>;
  MerchantBankDetails: ResolverTypeWrapper<MerchantBankDetails>;
  MerchantBankDetailsFailureResponse: ResolverTypeWrapper<MerchantBankDetailsFailureResponse>;
  MerchantBankDetailsResponse:
    | ResolversTypes['MerchantBankDetailsFailureResponse']
    | ResolversTypes['MerchantBankDetailsSuccessResponse'];
  MerchantBankDetailsSuccessResponse: ResolverTypeWrapper<MerchantBankDetailsSuccessResponse>;
  MerchantBankInput: MerchantBankInput;
  MerchantBankVerificationErrorCodeEnum: MerchantBankVerificationErrorCodeEnum;
  MerchantBankingAccount: ResolverTypeWrapper<MerchantBankingAccount>;
  MerchantBankingAccountBalance: ResolverTypeWrapper<MerchantBankingAccountBalance>;
  MerchantBankingAccountStatusEnum: MerchantBankingAccountStatusEnum;
  MerchantBankingAccountTypeEnum: MerchantBankingAccountTypeEnum;
  MerchantBankingAccountsBalanceResponse: ResolverTypeWrapper<MerchantBankingAccountsBalanceResponse>;
  MerchantBankingRole: ResolverTypeWrapper<MerchantBankingRole>;
  MerchantBusiness: ResolverTypeWrapper<MerchantBusiness>;
  MerchantBusinessAddress: ResolverTypeWrapper<MerchantBusinessAddress>;
  MerchantBusinessAddressInput: MerchantBusinessAddressInput;
  MerchantBusinessAppDetailsResponse: ResolverTypeWrapper<MerchantBusinessAppDetailsResponse>;
  MerchantBusinessAppInput: MerchantBusinessAppInput;
  MerchantBusinessCategoriesResponse: ResolverTypeWrapper<MerchantBusinessCategoriesResponse>;
  MerchantBusinessInput: MerchantBusinessInput;
  MerchantBusinessParentCategory: ResolverTypeWrapper<MerchantBusinessParentCategory>;
  MerchantBusinessSubCategory: ResolverTypeWrapper<MerchantBusinessSubCategory>;
  MerchantBusinessTypeEnum: MerchantBusinessTypeEnum;
  MerchantBusinessTypeField: ResolverTypeWrapper<MerchantBusinessTypeField>;
  MerchantBusinessTypeInputField: MerchantBusinessTypeInputField;
  MerchantBusinessTypesResponse: ResolverTypeWrapper<MerchantBusinessTypesResponse>;
  MerchantBusinessWebsiteDetailsResponse: ResolverTypeWrapper<MerchantBusinessWebsiteDetailsResponse>;
  MerchantBusinessWebsiteInput: MerchantBusinessWebsiteInput;
  MerchantClarificationDetail: ResolverTypeWrapper<MerchantClarificationDetail>;
  MerchantClarificationDetailsResponse: ResolverTypeWrapper<MerchantClarificationDetailsResponse>;
  MerchantClarificationDetailsSubmitResponse: ResolverTypeWrapper<MerchantClarificationDetailsSubmitResponse>;
  MerchantClarificationDetailsUpdateResponse: ResolverTypeWrapper<MerchantClarificationDetailsUpdateResponse>;
  MerchantClarificationFieldValues: ResolverTypeWrapper<MerchantClarificationFieldValues>;
  MerchantClarificationFromEnum: MerchantClarificationFromEnum;
  MerchantClarificationInputType: MerchantClarificationInputType;
  MerchantClarificationStatusEnum: MerchantClarificationStatusEnum;
  MerchantClarificationTypeEnum: MerchantClarificationTypeEnum;
  MerchantClarifications: ResolverTypeWrapper<MerchantClarifications>;
  MerchantCommentTypeEnum: MerchantCommentTypeEnum;
  MerchantConfig:
    | ResolversTypes['MerchantConfigFailure']
    | ResolversTypes['MerchantOnboardingConfig'];
  MerchantConfigFailure: ResolverTypeWrapper<MerchantConfigFailure>;
  MerchantConfigUpdateResponse: ResolverTypeWrapper<MerchantConfigUpdateResponse>;
  MerchantConfiguration: ResolverTypeWrapper<MerchantConfiguration>;
  MerchantConfigurationNamespaceEnum: MerchantConfigurationNamespaceEnum;
  MerchantConsentData: MerchantConsentData;
  MerchantConsentEvent: MerchantConsentEvent;
  MerchantConsentFailure: ResolverTypeWrapper<MerchantConsentFailure>;
  MerchantConsentInput: MerchantConsentInput;
  MerchantConsentPayload: MerchantConsentPayload;
  MerchantConsentResponse:
    | ResolversTypes['MerchantConsentFailure']
    | ResolversTypes['MerchantConsentSuccess'];
  MerchantConsentSuccess: ResolverTypeWrapper<MerchantConsentSuccess>;
  MerchantConsentsErrorTypeEnum: MerchantConsentsErrorTypeEnum;
  MerchantConsentsFailureResponse: ResolverTypeWrapper<MerchantConsentsFailureResponse>;
  MerchantConsentsResponse:
    | ResolversTypes['MerchantConsentsFailureResponse']
    | ResolversTypes['MerchantConsentsSuccessResponse'];
  MerchantConsentsSuccessResponse: ResolverTypeWrapper<MerchantConsentsSuccessResponse>;
  MerchantConsentsTypeEnum: MerchantConsentsTypeEnum;
  MerchantContact: ResolverTypeWrapper<MerchantContact>;
  MerchantContactCreateResponse: ResolverTypeWrapper<MerchantContactCreateResponse>;
  MerchantContactEmailOtpSendFailureResponse: ResolverTypeWrapper<MerchantContactEmailOtpSendFailureResponse>;
  MerchantContactEmailOtpSendResponse:
    | ResolversTypes['MerchantContactEmailOtpSendFailureResponse']
    | ResolversTypes['MerchantContactEmailOtpSendSuccessResponse'];
  MerchantContactEmailOtpSendSuccessResponse: ResolverTypeWrapper<MerchantContactEmailOtpSendSuccessResponse>;
  MerchantContactFundAccount: ResolverTypeWrapper<
    Omit<MerchantContactFundAccount, 'details'> & {
      details?: Maybe<ResolversTypes['MerchantContactFundAccountDetails']>;
    }
  >;
  MerchantContactFundAccountBankAccountInput: MerchantContactFundAccountBankAccountInput;
  MerchantContactFundAccountCreateResponse: ResolverTypeWrapper<MerchantContactFundAccountCreateResponse>;
  MerchantContactFundAccountDetails:
    | ResolversTypes['MerchantContactFundAccountDetailsBankAccount']
    | ResolversTypes['MerchantContactFundAccountDetailsCard']
    | ResolversTypes['MerchantContactFundAccountDetailsVPA']
    | ResolversTypes['MerchantContactFundAccountDetailsWallet'];
  MerchantContactFundAccountDetailsBankAccount: ResolverTypeWrapper<MerchantContactFundAccountDetailsBankAccount>;
  MerchantContactFundAccountDetailsCard: ResolverTypeWrapper<MerchantContactFundAccountDetailsCard>;
  MerchantContactFundAccountDetailsVPA: ResolverTypeWrapper<MerchantContactFundAccountDetailsVpa>;
  MerchantContactFundAccountDetailsWallet: ResolverTypeWrapper<MerchantContactFundAccountDetailsWallet>;
  MerchantContactFundAccountTypeEnum: MerchantContactFundAccountTypeEnum;
  MerchantContactFundAccountVPAInput: MerchantContactFundAccountVpaInput;
  MerchantContactFundAccountsResponse: ResolverTypeWrapper<MerchantContactFundAccountsResponse>;
  MerchantContactPerson: ResolverTypeWrapper<MerchantContactPerson>;
  MerchantContactPersonInput: MerchantContactPersonInput;
  MerchantContactTypeCreateResponse:
    | ResolversTypes['MerchantContactTypeCreateResponseDuplicate']
    | ResolversTypes['MerchantContactTypeCreateResponseSuccess'];
  MerchantContactTypeCreateResponseDuplicate: ResolverTypeWrapper<MerchantContactTypeCreateResponseDuplicate>;
  MerchantContactTypeCreateResponseSuccess: ResolverTypeWrapper<MerchantContactTypeCreateResponseSuccess>;
  MerchantContactUpdateResponse: ResolverTypeWrapper<MerchantContactUpdateResponse>;
  MerchantContactsResponse: ResolverTypeWrapper<MerchantContactsResponse>;
  MerchantCreditBalance: ResolverTypeWrapper<MerchantCreditBalance>;
  MerchantCreditBalanceFailureResponse: ResolverTypeWrapper<MerchantCreditBalanceFailureResponse>;
  MerchantCreditBalanceResponse:
    | ResolversTypes['MerchantCreditBalanceFailureResponse']
    | ResolversTypes['MerchantCreditBalanceSuccessResponse'];
  MerchantCreditBalanceSuccessResponse: ResolverTypeWrapper<MerchantCreditBalanceSuccessResponse>;
  MerchantDocument: ResolverTypeWrapper<MerchantDocument>;
  MerchantDocumentByIdResponse: ResolverTypeWrapper<MerchantDocumentByIdResponse>;
  MerchantDocumentField: ResolverTypeWrapper<MerchantDocumentField>;
  MerchantDocumentFieldValue: ResolverTypeWrapper<MerchantDocumentFieldValue>;
  MerchantDocumentFieldValueInterface:
    | ResolversTypes['MerchantDocumentByIdResponse']
    | ResolversTypes['MerchantDocumentFieldValue'];
  MerchantDocumentInput: MerchantDocumentInput;
  MerchantDocumentInputField: MerchantDocumentInputField;
  MerchantDocumentUpload: ResolverTypeWrapper<MerchantDocumentUpload>;
  MerchantDocumentUploadFailureResponse: ResolverTypeWrapper<MerchantDocumentUploadFailureResponse>;
  MerchantDocumentUploadPurposeEnum: MerchantDocumentUploadPurposeEnum;
  MerchantDocumentUploadResponse:
    | ResolversTypes['MerchantDocumentUploadFailureResponse']
    | ResolversTypes['MerchantDocumentUploadSuccessResponse'];
  MerchantDocumentUploadSuccessResponse: ResolverTypeWrapper<MerchantDocumentUploadSuccessResponse>;
  MerchantEmailField: ResolverTypeWrapper<MerchantEmailField>;
  MerchantEmailInputField: MerchantEmailInputField;
  MerchantEmailUpdateFailureResponse: ResolverTypeWrapper<MerchantEmailUpdateFailureResponse>;
  MerchantEmailUpdateResponse:
    | ResolversTypes['MerchantEmailUpdateFailureResponse']
    | ResolversTypes['MerchantEmailUpdateSuccessResponse'];
  MerchantEmailUpdateSuccessResponse: ResolverTypeWrapper<MerchantEmailUpdateSuccessResponse>;
  MerchantEscalationAction: ResolverTypeWrapper<MerchantEscalationAction>;
  MerchantEscalationLimit: ResolverTypeWrapper<MerchantEscalationLimit>;
  MerchantEscalationTypeEnum: MerchantEscalationTypeEnum;
  MerchantEscalations:
    | ResolversTypes['MerchantActivationEscalationsBreached']
    | ResolversTypes['MerchantActivationEscalationsNotBreached'];
  MerchantFeatureFlag: ResolverTypeWrapper<MerchantFeatureFlag>;
  MerchantFeeBasedGating: ResolverTypeWrapper<MerchantFeeBasedGating>;
  MerchantFieldClarificationReason: ResolverTypeWrapper<MerchantFieldClarificationReason>;
  MerchantFieldInterface:
    | ResolversTypes['MerchantAverageOrderField']
    | ResolversTypes['MerchantBusinessTypeField']
    | ResolversTypes['MerchantDocumentField']
    | ResolversTypes['MerchantEmailField']
    | ResolversTypes['MerchantNumberField']
    | ResolversTypes['MerchantPhoneField']
    | ResolversTypes['MerchantStringField']
    | ResolversTypes['MerchantURLField'];
  MerchantGstinUpdate: ResolverTypeWrapper<MerchantGstinUpdate>;
  MerchantGstinUpdateAsyncFlowSuccessResponse: ResolverTypeWrapper<MerchantGstinUpdateAsyncFlowSuccessResponse>;
  MerchantGstinUpdateCustomerActionEnum: MerchantGstinUpdateCustomerActionEnum;
  MerchantGstinUpdateFailureResponse: ResolverTypeWrapper<MerchantGstinUpdateFailureResponse>;
  MerchantGstinUpdateInSyncFlowResponse: ResolverTypeWrapper<MerchantGstinUpdateInSyncFlowResponse>;
  MerchantGstinUpdateInSyncWorkFlowCreatedResponse: ResolverTypeWrapper<MerchantGstinUpdateInSyncWorkFlowCreatedResponse>;
  MerchantGstinUpdateResponse:
    | ResolversTypes['MerchantGstinUpdateAsyncFlowSuccessResponse']
    | ResolversTypes['MerchantGstinUpdateFailureResponse']
    | ResolversTypes['MerchantGstinUpdateInSyncFlowResponse']
    | ResolversTypes['MerchantGstinUpdateInSyncWorkFlowCreatedResponse'];
  MerchantGstinWorkflowStatusEnum: MerchantGstinWorkflowStatusEnum;
  MerchantIdentityResponse: ResolverTypeWrapper<MerchantIdentityResponse>;
  MerchantIdentityTypeEnum: MerchantIdentityTypeEnum;
  MerchantKYCPartnerAccessErrorTypeEnum: MerchantKycPartnerAccessErrorTypeEnum;
  MerchantKYCPartnerAccessInputTypeEnum: MerchantKycPartnerAccessInputTypeEnum;
  MerchantKYCPartnerAccessResponse: ResolverTypeWrapper<MerchantKycPartnerAccessResponse>;
  MerchantKYCPartnerAccessStatusUpdateResponse:
    | ResolversTypes['MerchantKYCPartnerAccessUpdateFailureResponse']
    | ResolversTypes['MerchantKYCPartnerAccessUpdateSuccessResponse'];
  MerchantKYCPartnerAccessTypeEnum: MerchantKycPartnerAccessTypeEnum;
  MerchantKYCPartnerAccessUpdateFailureResponse: ResolverTypeWrapper<MerchantKycPartnerAccessUpdateFailureResponse>;
  MerchantKYCPartnerAccessUpdateSuccessResponse: ResolverTypeWrapper<MerchantKycPartnerAccessUpdateSuccessResponse>;
  MerchantMonthlyRevenueEnum: MerchantMonthlyRevenueEnum;
  MerchantName: ResolverTypeWrapper<MerchantName>;
  MerchantNcEligibilityResponse: ResolverTypeWrapper<MerchantNcEligibilityResponse>;
  MerchantNumberField: ResolverTypeWrapper<MerchantNumberField>;
  MerchantOnboardingConfig: ResolverTypeWrapper<MerchantOnboardingConfig>;
  MerchantOnboardingConfigurationInput: MerchantOnboardingConfigurationInput;
  MerchantOnboardingQuestionDetail: ResolverTypeWrapper<MerchantOnboardingQuestionDetail>;
  MerchantOnboardingQuestionDetailInput: MerchantOnboardingQuestionDetailInput;
  MerchantOnboardingQuestionDetailsFailureResponse: ResolverTypeWrapper<MerchantOnboardingQuestionDetailsFailureResponse>;
  MerchantOnboardingQuestionDetailsResponse:
    | ResolversTypes['MerchantOnboardingQuestionDetailsFailureResponse']
    | ResolversTypes['MerchantOnboardingQuestionDetailsSuccessResponse'];
  MerchantOnboardingQuestionDetailsSuccessResponse: ResolverTypeWrapper<MerchantOnboardingQuestionDetailsSuccessResponse>;
  MerchantOnboardingQuestionDetailsUpdateResponse: ResolverTypeWrapper<MerchantOnboardingQuestionDetailsUpdateResponse>;
  MerchantPaymentAcceptanceChannels: ResolverTypeWrapper<MerchantPaymentAcceptanceChannels>;
  MerchantPaymentAcceptanceChannelsInput: MerchantPaymentAcceptanceChannelsInput;
  MerchantPaymentHandle: ResolverTypeWrapper<MerchantPaymentHandle>;
  MerchantPaymentHandleAvailabilityFailureResponse: ResolverTypeWrapper<MerchantPaymentHandleAvailabilityFailureResponse>;
  MerchantPaymentHandleAvailabilityResponse:
    | ResolversTypes['MerchantPaymentHandleAvailabilityFailureResponse']
    | ResolversTypes['MerchantPaymentHandleAvailabilitySuccessResponse'];
  MerchantPaymentHandleAvailabilitySuccessResponse: ResolverTypeWrapper<MerchantPaymentHandleAvailabilitySuccessResponse>;
  MerchantPaymentHandleCreateFailureResponse: ResolverTypeWrapper<MerchantPaymentHandleCreateFailureResponse>;
  MerchantPaymentHandleCreateResponse:
    | ResolversTypes['MerchantPaymentHandleCreateFailureResponse']
    | ResolversTypes['MerchantPaymentHandleCreateSuccessResponse'];
  MerchantPaymentHandleCreateSuccessResponse: ResolverTypeWrapper<MerchantPaymentHandleCreateSuccessResponse>;
  MerchantPaymentHandleEncryptedAmountFailureResponse: ResolverTypeWrapper<MerchantPaymentHandleEncryptedAmountFailureResponse>;
  MerchantPaymentHandleEncryptedAmountResponse:
    | ResolversTypes['MerchantPaymentHandleEncryptedAmountFailureResponse']
    | ResolversTypes['MerchantPaymentHandleEncryptedAmountSuccessResponse'];
  MerchantPaymentHandleEncryptedAmountSuccessResponse: ResolverTypeWrapper<MerchantPaymentHandleEncryptedAmountSuccessResponse>;
  MerchantPaymentHandleFailureResponse: ResolverTypeWrapper<MerchantPaymentHandleFailureResponse>;
  MerchantPaymentHandleResponse:
    | ResolversTypes['MerchantPaymentHandleFailureResponse']
    | ResolversTypes['MerchantPaymentHandleSuccessResponse'];
  MerchantPaymentHandleSuccessResponse: ResolverTypeWrapper<MerchantPaymentHandleSuccessResponse>;
  MerchantPaymentHandleSuggestionsResponse: ResolverTypeWrapper<MerchantPaymentHandleSuggestionsResponse>;
  MerchantPaymentHandleUpdateFailureResponse: ResolverTypeWrapper<MerchantPaymentHandleUpdateFailureResponse>;
  MerchantPaymentHandleUpdateResponse:
    | ResolversTypes['MerchantPaymentHandleUpdateFailureResponse']
    | ResolversTypes['MerchantPaymentHandleUpdateSuccessResponse'];
  MerchantPaymentHandleUpdateSuccessResponse: ResolverTypeWrapper<MerchantPaymentHandleUpdateSuccessResponse>;
  MerchantPhoneField: ResolverTypeWrapper<MerchantPhoneField>;
  MerchantPhoneInputField: MerchantPhoneInputField;
  MerchantPolicyEmptyPreviewResponse: ResolverTypeWrapper<MerchantPolicyEmptyPreviewResponse>;
  MerchantPolicyEmptyResponse: ResolverTypeWrapper<MerchantPolicyEmptyResponse>;
  MerchantPolicyEmptyV2PreviewResponse: ResolverTypeWrapper<MerchantPolicyEmptyV2PreviewResponse>;
  MerchantPolicyFailureResponse: ResolverTypeWrapper<MerchantPolicyFailureResponse>;
  MerchantPolicyPreview: ResolverTypeWrapper<MerchantPolicyPreview>;
  MerchantPolicyPreviewFailureResponse: ResolverTypeWrapper<MerchantPolicyPreviewFailureResponse>;
  MerchantPolicyPreviewResponse:
    | ResolversTypes['MerchantPolicyEmptyPreviewResponse']
    | ResolversTypes['MerchantPolicyPreviewFailureResponse']
    | ResolversTypes['MerchantPolicyPreviewSuccessResponse'];
  MerchantPolicyPreviewSuccessResponse: ResolverTypeWrapper<MerchantPolicyPreviewSuccessResponse>;
  MerchantPolicyPreviewV2FailureResponse: ResolverTypeWrapper<MerchantPolicyPreviewV2FailureResponse>;
  MerchantPolicyPreviewV2Response:
    | ResolversTypes['MerchantPolicyEmptyV2PreviewResponse']
    | ResolversTypes['MerchantPolicyPreviewV2FailureResponse']
    | ResolversTypes['MerchantPolicyPreviewV2SuccessResponse'];
  MerchantPolicyPreviewV2SuccessResponse: ResolverTypeWrapper<MerchantPolicyPreviewV2SuccessResponse>;
  MerchantPolicyPublishFailureResponse: ResolverTypeWrapper<MerchantPolicyPublishFailureResponse>;
  MerchantPolicyPublishResponse:
    | ResolversTypes['MerchantPolicyPublishFailureResponse']
    | ResolversTypes['MerchantPolicyPublishSuccessResponse'];
  MerchantPolicyPublishSuccessResponse: ResolverTypeWrapper<MerchantPolicyPublishSuccessResponse>;
  MerchantPolicyResponse:
    | ResolversTypes['MerchantPolicyEmptyResponse']
    | ResolversTypes['MerchantPolicyFailureResponse']
    | ResolversTypes['MerchantPolicySuccessResponse'];
  MerchantPolicySuccessResponse: ResolverTypeWrapper<MerchantPolicySuccessResponse>;
  MerchantPolicyWizardV2EligibilityResponse: ResolverTypeWrapper<MerchantPolicyWizardV2EligibilityResponse>;
  MerchantPreference: ResolverTypeWrapper<MerchantPreference>;
  MerchantPreferenceProductTypeEnum: MerchantPreferenceProductTypeEnum;
  MerchantReferralFailureResponse: ResolverTypeWrapper<MerchantReferralFailureResponse>;
  MerchantReferralResponse:
    | ResolversTypes['MerchantReferralFailureResponse']
    | ResolversTypes['MerchantReferralSuccessResponse'];
  MerchantReferralSuccessResponse: ResolverTypeWrapper<MerchantReferralSuccessResponse>;
  MerchantRoleEnum: MerchantRoleEnum;
  MerchantSelfServeGstinPermissionEnum: MerchantSelfServeGstinPermissionEnum;
  MerchantSelfServeWorkflow: ResolverTypeWrapper<MerchantSelfServeWorkflow>;
  MerchantSelfServeWorkflowEnum: MerchantSelfServeWorkflowEnum;
  MerchantSelfServeWorkflowStatusFailureResponse: ResolverTypeWrapper<MerchantSelfServeWorkflowStatusFailureResponse>;
  MerchantSelfServeWorkflowStatusResponse:
    | ResolversTypes['MerchantSelfServeWorkflowStatusFailureResponse']
    | ResolversTypes['MerchantSelfServeWorkflowStatusSuccessResponse'];
  MerchantSelfServeWorkflowStatusSuccessResponse: ResolverTypeWrapper<MerchantSelfServeWorkflowStatusSuccessResponse>;
  MerchantSettlementConfigFailureResponse: ResolverTypeWrapper<MerchantSettlementConfigFailureResponse>;
  MerchantSettlementConfigResponse:
    | ResolversTypes['MerchantSettlementConfigFailureResponse']
    | ResolversTypes['MerchantSettlementConfigSuccessResponse'];
  MerchantSettlementConfigSuccessResponse: ResolverTypeWrapper<MerchantSettlementConfigSuccessResponse>;
  MerchantShopEstablishment: ResolverTypeWrapper<MerchantShopEstablishment>;
  MerchantShopEstablishmentInput: MerchantShopEstablishmentInput;
  MerchantSocialMediaURLField: ResolverTypeWrapper<MerchantSocialMediaUrlField>;
  MerchantSocialMediaURLInputField: MerchantSocialMediaUrlInputField;
  MerchantStakeholder: ResolverTypeWrapper<MerchantStakeholder>;
  MerchantStakeholderInput: MerchantStakeholderInput;
  MerchantStringField: ResolverTypeWrapper<MerchantStringField>;
  MerchantStringInputField: MerchantStringInputField;
  MerchantSupportDetails: ResolverTypeWrapper<MerchantSupportDetails>;
  MerchantSupportDetailsFailureResponse: ResolverTypeWrapper<MerchantSupportDetailsFailureResponse>;
  MerchantSupportDetailsResponse:
    | ResolversTypes['MerchantSupportDetailsFailureResponse']
    | ResolversTypes['MerchantSupportDetailsSuccessResponse'];
  MerchantSupportDetailsSuccessResponse: ResolverTypeWrapper<MerchantSupportDetailsSuccessResponse>;
  MerchantSwitchResponse: ResolverTypeWrapper<MerchantSwitchResponse>;
  MerchantTransactionLimit: ResolverTypeWrapper<MerchantTransactionLimit>;
  MerchantURLField: ResolverTypeWrapper<MerchantUrlField>;
  MerchantURLInputField: MerchantUrlInputField;
  MerchantValidateSocialMediaURLResponse: ResolverTypeWrapper<MerchantValidateSocialMediaUrlResponse>;
  MerchantVerificationStatusEnum: MerchantVerificationStatusEnum;
  MerchantVirtualAccount: ResolverTypeWrapper<MerchantVirtualAccount>;
  MerchantVirtualAccountReceiver: ResolverTypeWrapper<MerchantVirtualAccountReceiver>;
  MerchantVirtualAccountStatus: MerchantVirtualAccountStatus;
  MerchantVirtualAccountsResponse: ResolverTypeWrapper<MerchantVirtualAccountsResponse>;
  MerchantWebsite: ResolverTypeWrapper<MerchantWebsite>;
  MerchantWebsiteActionEnum: MerchantWebsiteActionEnum;
  MerchantWebsiteAdditionalData: ResolverTypeWrapper<MerchantWebsiteAdditionalData>;
  MerchantWebsiteAdditionalDataInput: MerchantWebsiteAdditionalDataInput;
  MerchantWebsiteApplicationDetail: ResolverTypeWrapper<MerchantWebsiteApplicationDetail>;
  MerchantWebsiteApplicationInput: MerchantWebsiteApplicationInput;
  MerchantWebsiteApprovalStatusEnum: MerchantWebsiteApprovalStatusEnum;
  MerchantWebsiteDetailsFailureResponse: ResolverTypeWrapper<MerchantWebsiteDetailsFailureResponse>;
  MerchantWebsiteDetailsResponse: ResolverTypeWrapper<MerchantWebsiteDetailsResponse>;
  MerchantWebsiteDetailsSubmitEnum: MerchantWebsiteDetailsSubmitEnum;
  MerchantWebsiteDocumentDeleteFailureResponse: ResolverTypeWrapper<MerchantWebsiteDocumentDeleteFailureResponse>;
  MerchantWebsiteDocumentDeleteResponse:
    | ResolversTypes['MerchantWebsiteDocumentDeleteFailureResponse']
    | ResolversTypes['MerchantWebsiteDocumentDeleteSuccessResponse'];
  MerchantWebsiteDocumentDeleteSuccessResponse: ResolverTypeWrapper<MerchantWebsiteDocumentDeleteSuccessResponse>;
  MerchantWebsiteDocumentUploadFailureResponse: ResolverTypeWrapper<MerchantWebsiteDocumentUploadFailureResponse>;
  MerchantWebsiteDocumentUploadResponse:
    | ResolversTypes['MerchantWebsiteDocumentUploadFailureResponse']
    | ResolversTypes['MerchantWebsiteDocumentUploadSuccessResponse'];
  MerchantWebsiteDocumentUploadSuccessResponse: ResolverTypeWrapper<MerchantWebsiteDocumentUploadSuccessResponse>;
  MerchantWebsitePlatformUrlsEnum: MerchantWebsitePlatformUrlsEnum;
  MerchantWebsitePublishFailureResponse: ResolverTypeWrapper<MerchantWebsitePublishFailureResponse>;
  MerchantWebsitePublishResponse:
    | ResolversTypes['MerchantWebsitePublishFailureResponse']
    | ResolversTypes['MerchantWebsitePublishSuccessResponse'];
  MerchantWebsitePublishSuccessResponse: ResolverTypeWrapper<MerchantWebsitePublishSuccessResponse>;
  MerchantWebsiteSection: ResolverTypeWrapper<MerchantWebsiteSection>;
  MerchantWebsiteSectionEnum: MerchantWebsiteSectionEnum;
  MerchantWebsiteSectionInput: MerchantWebsiteSectionInput;
  MerchantWebsiteSectionStatusEnum: MerchantWebsiteSectionStatusEnum;
  MerchantWebsiteTermsAndConditions: ResolverTypeWrapper<MerchantWebsiteTermsAndConditions>;
  MerchantWebsitesResponse:
    | ResolversTypes['MerchantWebsiteDetailsFailureResponse']
    | ResolversTypes['MerchantWebsiteDetailsResponse'];
  MerchantWorkflowClarificationSubmitFailureResponse: ResolverTypeWrapper<MerchantWorkflowClarificationSubmitFailureResponse>;
  MerchantWorkflowClarificationSubmitResponse:
    | ResolversTypes['MerchantWorkflowClarificationSubmitFailureResponse']
    | ResolversTypes['MerchantWorkflowClarificationSubmitSuccessResponse'];
  MerchantWorkflowClarificationSubmitSuccessResponse: ResolverTypeWrapper<MerchantWorkflowClarificationSubmitSuccessResponse>;
  Money: ResolverTypeWrapper<Money>;
  MoneyInput: MoneyInput;
  Mutation: ResolverTypeWrapper<{}>;
  MutationResponseInterface:
    | ResolversTypes['AadhaarCaptchaVerifyResponse']
    | ResolversTypes['AadhaarOtpVerifyResponse']
    | ResolversTypes['ApproveIciciPayoutResponse']
    | ResolversTypes['ApprovePayoutResponse']
    | ResolversTypes['AuthUser']
    | ResolversTypes['CouponApplyResponse']
    | ResolversTypes['CouponValidateResponse']
    | ResolversTypes['DeregisterFCMTokenResponse']
    | ResolversTypes['LoginOtpError']
    | ResolversTypes['LoginOtpSuccess']
    | ResolversTypes['MerchantActivationResponse']
    | ResolversTypes['MerchantApiKeyCreateResponse']
    | ResolversTypes['MerchantApiKeyRegenerateResponse']
    | ResolversTypes['MerchantApiKeysCreateFailure']
    | ResolversTypes['MerchantApiKeysCreateSuccess']
    | ResolversTypes['MerchantBankAccountDocumentUploadSuccessResponse']
    | ResolversTypes['MerchantBankAccountUpdateFailureResponse']
    | ResolversTypes['MerchantBankAccountUpdateSuccessResponse']
    | ResolversTypes['MerchantBusinessAppDetailsResponse']
    | ResolversTypes['MerchantBusinessWebsiteDetailsResponse']
    | ResolversTypes['MerchantClarificationDetailsSubmitResponse']
    | ResolversTypes['MerchantClarificationDetailsUpdateResponse']
    | ResolversTypes['MerchantConfigUpdateResponse']
    | ResolversTypes['MerchantConsentFailure']
    | ResolversTypes['MerchantContactCreateResponse']
    | ResolversTypes['MerchantContactEmailOtpSendFailureResponse']
    | ResolversTypes['MerchantContactEmailOtpSendSuccessResponse']
    | ResolversTypes['MerchantContactFundAccountCreateResponse']
    | ResolversTypes['MerchantContactUpdateResponse']
    | ResolversTypes['MerchantDocumentUploadSuccessResponse']
    | ResolversTypes['MerchantGstinUpdateAsyncFlowSuccessResponse']
    | ResolversTypes['MerchantGstinUpdateInSyncFlowResponse']
    | ResolversTypes['MerchantGstinUpdateInSyncWorkFlowCreatedResponse']
    | ResolversTypes['MerchantNcEligibilityResponse']
    | ResolversTypes['MerchantPaymentHandleCreateSuccessResponse']
    | ResolversTypes['MerchantPaymentHandleUpdateSuccessResponse']
    | ResolversTypes['MerchantPolicyPublishSuccessResponse']
    | ResolversTypes['MerchantPolicyWizardV2EligibilityResponse']
    | ResolversTypes['MerchantSwitchResponse']
    | ResolversTypes['MerchantWebsiteDocumentDeleteSuccessResponse']
    | ResolversTypes['MerchantWebsiteDocumentUploadSuccessResponse']
    | ResolversTypes['MerchantWebsitePublishSuccessResponse']
    | ResolversTypes['MerchantWorkflowClarificationSubmitSuccessResponse']
    | ResolversTypes['NotificationEmailUpdateFailureResponse']
    | ResolversTypes['NotificationEmailUpdateSuccessResponse']
    | ResolversTypes['NotificationWhatsAppOptIn']
    | ResolversTypes['OauthTokenAppleWatchOtp']
    | ResolversTypes['OauthTokenAppleWatchResponseError']
    | ResolversTypes['OnboardingPaymentOrderCreateFailureResponse']
    | ResolversTypes['OnboardingPaymentOrderCreateSuccessResponse']
    | ResolversTypes['OnboardingPaymentOrderVerifyResponse']
    | ResolversTypes['OrderCreateFailureResponse']
    | ResolversTypes['OrderCreateSuccessResponse']
    | ResolversTypes['PaymentCaptureResponse']
    | ResolversTypes['PaymentLinkCancelResponse']
    | ResolversTypes['PaymentLinkCreateResponse']
    | ResolversTypes['PaymentLinkNotifyResponse']
    | ResolversTypes['PaymentRefundResponse']
    | ResolversTypes['PaymentsNewLaunchProductViewUpdate']
    | ResolversTypes['PaymentsProductFtuxUpdateResponse']
    | ResolversTypes['PayoutCompositeCreateResponse']
    | ResolversTypes['PayoutCreateIciciResponse']
    | ResolversTypes['PayoutCreateResponse']
    | ResolversTypes['PayoutLinkCreateResponse']
    | ResolversTypes['PayoutPurposeCreateResponse']
    | ResolversTypes['PointOfSalePaymentCreateFailureResponse']
    | ResolversTypes['PointOfSalePaymentCreateSuccessResponse']
    | ResolversTypes['PointOfSalePaymentUpdateFailureResponse']
    | ResolversTypes['PointOfSalePaymentUpdateSuccessResponse']
    | ResolversTypes['QRCodeCreateFailureResponse']
    | ResolversTypes['QRCodeCreateSuccessResponse']
    | ResolversTypes['RegisterBusinessResponse']
    | ResolversTypes['RegisterEmailError']
    | ResolversTypes['RegisterEmailSuccess']
    | ResolversTypes['RegisterFCMTokenResponse']
    | ResolversTypes['RegisterMerchantResponseFailure']
    | ResolversTypes['RegisterMerchantResponseSuccess']
    | ResolversTypes['RegisterMobileVerifyResponseFailure']
    | ResolversTypes['RegisterMobileVerifyResponseSuccess']
    | ResolversTypes['RejectPayoutResponse']
    | ResolversTypes['ResendEmailOtp']
    | ResolversTypes['ResendTwoFactorLoginOtpResponse']
    | ResolversTypes['SendApprovePayoutBatchOtp']
    | ResolversTypes['SendApprovePayoutOtp']
    | ResolversTypes['SendCreatePayoutLinkOtp']
    | ResolversTypes['SendCreatePayoutOtp']
    | ResolversTypes['SendIciciPayoutOtpResponse']
    | ResolversTypes['SendPayoutApproveBulkOtp']
    | ResolversTypes['SendPayoutCompositeOtp']
    | ResolversTypes['SmsNotificationToggle']
    | ResolversTypes['TwoFactorAddMobileOtpErrorResponse']
    | ResolversTypes['TwoFactorAddMobileOtpSuccessResponse']
    | ResolversTypes['TwoFactorAddMobileOtpVerifyErrorResponse']
    | ResolversTypes['TwoFactorAddMobileOtpVerifySuccessResponse']
    | ResolversTypes['TwoFactorEmailOtpVerifyFailureResponse']
    | ResolversTypes['TwoFactorEmailOtpVerifySuccessResponse']
    | ResolversTypes['TwoFactorOtpFailureResponse']
    | ResolversTypes['TwoFactorOtpSuccessResponse']
    | ResolversTypes['TwoFactorPasswordCreateErrorResponse']
    | ResolversTypes['TwoFactorPasswordCreateSuccessResponse']
    | ResolversTypes['TwoFactorUnverifiedMobileVerifyResponse']
    | ResolversTypes['UpdateMerchantConsentResponse']
    | ResolversTypes['UserContactDetailsUpdateResponse']
    | ResolversTypes['UserDeviceAnalyticsResponse']
    | ResolversTypes['UserOtpVerifyResponse']
    | ResolversTypes['VendorPaymentCancelResponse']
    | ResolversTypes['VendorPaymentPayoutCreateResponse']
    | ResolversTypes['WhatsappNotificationToggle']
    | ResolversTypes['merchantConfigurationUpdateResponse'];
  NonNegativeInt: ResolverTypeWrapper<Scalars['NonNegativeInt']>;
  NotificationEmailUpdateFailureResponse: ResolverTypeWrapper<NotificationEmailUpdateFailureResponse>;
  NotificationEmailUpdateResponse:
    | ResolversTypes['NotificationEmailUpdateFailureResponse']
    | ResolversTypes['NotificationEmailUpdateSuccessResponse'];
  NotificationEmailUpdateSuccessResponse: ResolverTypeWrapper<NotificationEmailUpdateSuccessResponse>;
  NotificationWhatsAppOptIn: ResolverTypeWrapper<NotificationWhatsAppOptIn>;
  OAuthProviderEnum: OAuthProviderEnum;
  OauthTokenAppleWatchOtp: ResolverTypeWrapper<OauthTokenAppleWatchOtp>;
  OauthTokenAppleWatchResponse:
    | ResolversTypes['OauthTokenAppleWatchResponseError']
    | ResolversTypes['OauthTokenAppleWatchResponseSuccess'];
  OauthTokenAppleWatchResponseError: ResolverTypeWrapper<OauthTokenAppleWatchResponseError>;
  OauthTokenAppleWatchResponseSuccess: ResolverTypeWrapper<OauthTokenAppleWatchResponseSuccess>;
  OnboardingPaymentOrderCreateFailureResponse: ResolverTypeWrapper<OnboardingPaymentOrderCreateFailureResponse>;
  OnboardingPaymentOrderCreateResponse:
    | ResolversTypes['OnboardingPaymentOrderCreateFailureResponse']
    | ResolversTypes['OnboardingPaymentOrderCreateSuccessResponse'];
  OnboardingPaymentOrderCreateSuccessResponse: ResolverTypeWrapper<OnboardingPaymentOrderCreateSuccessResponse>;
  OnboardingPaymentOrderVerifyResponse: ResolverTypeWrapper<OnboardingPaymentOrderVerifyResponse>;
  OnboardingWidget: ResolverTypeWrapper<OnboardingWidget>;
  Order: ResolverTypeWrapper<Order>;
  OrderAmount: ResolverTypeWrapper<OrderAmount>;
  OrderCreateFailureResponse: ResolverTypeWrapper<OrderCreateFailureResponse>;
  OrderCreateResponse:
    | ResolversTypes['OrderCreateFailureResponse']
    | ResolversTypes['OrderCreateSuccessResponse'];
  OrderCreateSuccessResponse: ResolverTypeWrapper<OrderCreateSuccessResponse>;
  OrderDate: ResolverTypeWrapper<OrderDate>;
  OrderStatusEnum: OrderStatusEnum;
  Organisation: ResolverTypeWrapper<Organisation>;
  OrganisationEmail: ResolverTypeWrapper<OrganisationEmail>;
  OrganisationLogo: ResolverTypeWrapper<OrganisationLogo>;
  OrganisationName: ResolverTypeWrapper<OrganisationName>;
  OverViewResponseType: ResolverTypeWrapper<OverViewResponseType>;
  PPTrackingSettings: ResolverTypeWrapper<PpTrackingSettings>;
  PageAcquirerData: ResolverTypeWrapper<PageAcquirerData>;
  PageItem: ResolverTypeWrapper<PageItem>;
  PageItemTaxDetails: ResolverTypeWrapper<PageItemTaxDetails>;
  PaginationResponseInterface:
    | ResolversTypes['InvoicesResponse']
    | ResolversTypes['MerchantContactFundAccountsResponse']
    | ResolversTypes['MerchantContactsResponse']
    | ResolversTypes['MerchantVirtualAccountsResponse']
    | ResolversTypes['PaymentLinksResponse']
    | ResolversTypes['PaymentPageTransactionResponse']
    | ResolversTypes['PaymentPagesResponse']
    | ResolversTypes['PaymentsResponse']
    | ResolversTypes['PayoutBatchesResponse']
    | ResolversTypes['PayoutLinksResponse']
    | ResolversTypes['PayoutsResponse']
    | ResolversTypes['QRCodesResponse']
    | ResolversTypes['RefundsResponse']
    | ResolversTypes['SettlementsResponse']
    | ResolversTypes['TransactionsResponse']
    | ResolversTypes['VendorPaymentsResponse'];
  PartnerConfigFailure: ResolverTypeWrapper<PartnerConfigFailure>;
  PartnerConfigResponse:
    | ResolversTypes['PartnerConfigFailure']
    | ResolversTypes['PartnerConfigSuccess'];
  PartnerConfigSuccess: ResolverTypeWrapper<PartnerConfigSuccess>;
  PartnerWebhookSettings: ResolverTypeWrapper<PartnerWebhookSettings>;
  Payment: ResolverTypeWrapper<
    Omit<Payment, 'method'> & { method?: Maybe<ResolversTypes['PaymentMethod']> }
  >;
  PaymentAggregationSummary: ResolverTypeWrapper<PaymentAggregationSummary>;
  PaymentAmount: ResolverTypeWrapper<PaymentAmount>;
  PaymentAnalytics: ResolverTypeWrapper<PaymentAnalytics>;
  PaymentAnalyticsAggregateByEnum: PaymentAnalyticsAggregateByEnum;
  PaymentAnalyticsFilterBy: PaymentAnalyticsFilterBy;
  PaymentAnalyticsFilterByDeviceEnum: PaymentAnalyticsFilterByDeviceEnum;
  PaymentAnalyticsFilterByOsEnum: PaymentAnalyticsFilterByOsEnum;
  PaymentAnalyticsFilterByPaymentEnum: PaymentAnalyticsFilterByPaymentEnum;
  PaymentAnalyticsFilterBySdkEnum: PaymentAnalyticsFilterBySdkEnum;
  PaymentAnalyticsIntervalEnum: PaymentAnalyticsIntervalEnum;
  PaymentAnalyticsResponse: ResolverTypeWrapper<PaymentAnalyticsResponse>;
  PaymentAnalyticsWidget: ResolverTypeWrapper<PaymentAnalyticsWidget>;
  PaymentApplicationEnum: PaymentApplicationEnum;
  PaymentCaptureResponse: ResolverTypeWrapper<PaymentCaptureResponse>;
  PaymentDetails: ResolverTypeWrapper<PaymentDetails>;
  PaymentEmiDetails: ResolverTypeWrapper<PaymentEmiDetails>;
  PaymentError: ResolverTypeWrapper<PaymentError>;
  PaymentHandleWidget: ResolverTypeWrapper<PaymentHandleWidget>;
  PaymentInstantRefundEligibilityAmount: ResolverTypeWrapper<PaymentInstantRefundEligibilityAmount>;
  PaymentInstantRefundEligibilityOptionEnum: PaymentInstantRefundEligibilityOptionEnum;
  PaymentInstantRefundEligibilityResponse: ResolverTypeWrapper<PaymentInstantRefundEligibilityResponse>;
  PaymentLink: ResolverTypeWrapper<PaymentLink>;
  PaymentLinkAmount: ResolverTypeWrapper<PaymentLinkAmount>;
  PaymentLinkCancelResponse: ResolverTypeWrapper<PaymentLinkCancelResponse>;
  PaymentLinkCreateResponse: ResolverTypeWrapper<PaymentLinkCreateResponse>;
  PaymentLinkDate: ResolverTypeWrapper<PaymentLinkDate>;
  PaymentLinkNotifyBy: ResolverTypeWrapper<PaymentLinkNotifyBy>;
  PaymentLinkNotifyByInput: PaymentLinkNotifyByInput;
  PaymentLinkNotifyMedium: PaymentLinkNotifyMedium;
  PaymentLinkNotifyResponse: ResolverTypeWrapper<PaymentLinkNotifyResponse>;
  PaymentLinkReminder: ResolverTypeWrapper<PaymentLinkReminder>;
  PaymentLinkReminderStatusEnum: PaymentLinkReminderStatusEnum;
  PaymentLinkStatusEnum: PaymentLinkStatusEnum;
  PaymentLinksResponse: ResolverTypeWrapper<PaymentLinksResponse>;
  PaymentMethod:
    | ResolversTypes['PaymentMethodApp']
    | ResolversTypes['PaymentMethodBankTransfer']
    | ResolversTypes['PaymentMethodCard']
    | ResolversTypes['PaymentMethodCardlessEmi']
    | ResolversTypes['PaymentMethodEmandate']
    | ResolversTypes['PaymentMethodEmi']
    | ResolversTypes['PaymentMethodNetBanking']
    | ResolversTypes['PaymentMethodPayLater']
    | ResolversTypes['PaymentMethodUPITransfer']
    | ResolversTypes['PaymentMethodWallet'];
  PaymentMethodApp: ResolverTypeWrapper<PaymentMethodApp>;
  PaymentMethodBankTransfer: ResolverTypeWrapper<PaymentMethodBankTransfer>;
  PaymentMethodCard: ResolverTypeWrapper<PaymentMethodCard>;
  PaymentMethodCardCategoryEnum: PaymentMethodCardCategoryEnum;
  PaymentMethodCardExpiry: ResolverTypeWrapper<PaymentMethodCardExpiry>;
  PaymentMethodCardType: PaymentMethodCardType;
  PaymentMethodCardlessEmi: ResolverTypeWrapper<PaymentMethodCardlessEmi>;
  PaymentMethodEmandate: ResolverTypeWrapper<PaymentMethodEmandate>;
  PaymentMethodEmi: ResolverTypeWrapper<PaymentMethodEmi>;
  PaymentMethodEnum: PaymentMethodEnum;
  PaymentMethodNetBanking: ResolverTypeWrapper<PaymentMethodNetBanking>;
  PaymentMethodPayLater: ResolverTypeWrapper<PaymentMethodPayLater>;
  PaymentMethodUPITransfer: ResolverTypeWrapper<PaymentMethodUpiTransfer>;
  PaymentMethodWallet: ResolverTypeWrapper<PaymentMethodWallet>;
  PaymentOverviewResponse: ResolverTypeWrapper<PaymentOverviewResponse>;
  PaymentPage: ResolverTypeWrapper<PaymentPage>;
  PaymentPageAmount: ResolverTypeWrapper<PaymentPageAmount>;
  PaymentPageDate: ResolverTypeWrapper<PaymentPageDate>;
  PaymentPageItem: ResolverTypeWrapper<PaymentPageItem>;
  PaymentPageSettings: ResolverTypeWrapper<PaymentPageSettings>;
  PaymentPageStatusEnum: PaymentPageStatusEnum;
  PaymentPageSupportDetails: ResolverTypeWrapper<PaymentPageSupportDetails>;
  PaymentPageTransaction: ResolverTypeWrapper<PaymentPageTransaction>;
  PaymentPageTransactionResponse: ResolverTypeWrapper<PaymentPageTransactionResponse>;
  PaymentPagesResponse: ResolverTypeWrapper<PaymentPagesResponse>;
  PaymentPayerBankAccount: ResolverTypeWrapper<PaymentPayerBankAccount>;
  PaymentRefund: ResolverTypeWrapper<PaymentRefund>;
  PaymentRefundResponse: ResolverTypeWrapper<PaymentRefundResponse>;
  PaymentRefundSpeed: ResolverTypeWrapper<PaymentRefundSpeed>;
  PaymentRefundSpeedProcessedEnum: PaymentRefundSpeedProcessedEnum;
  PaymentRefundSpeedRequestedEnum: PaymentRefundSpeedRequestedEnum;
  PaymentRefundStatusEnum: PaymentRefundStatusEnum;
  PaymentStatusEnum: PaymentStatusEnum;
  PaymentSummaryAggregationFieldEnum: PaymentSummaryAggregationFieldEnum;
  PaymentSummaryFilterFieldEnum: PaymentSummaryFilterFieldEnum;
  PaymentSummaryIndexEnum: PaymentSummaryIndexEnum;
  PaymentSummaryResponse: ResolverTypeWrapper<PaymentSummaryResponse>;
  PaymentTerm: ResolverTypeWrapper<PaymentTerm>;
  PaymentVirtualAccount: ResolverTypeWrapper<PaymentVirtualAccount>;
  PaymentVirtualAccountAmount: ResolverTypeWrapper<PaymentVirtualAccountAmount>;
  PaymentVirtualAccountDates: ResolverTypeWrapper<PaymentVirtualAccountDates>;
  PaymentsNewLaunchProductViewUpdate: ResolverTypeWrapper<PaymentsNewLaunchProductViewUpdate>;
  PaymentsProductFtuxUpdateResponse: ResolverTypeWrapper<PaymentsProductFtuxUpdateResponse>;
  PaymentsResponse: ResolverTypeWrapper<PaymentsResponse>;
  PaymentsSegmentEnum: PaymentsSegmentEnum;
  PaymentsWidgetError: ResolverTypeWrapper<PaymentsWidgetError>;
  PaymentsWidgetTypeEnum: PaymentsWidgetTypeEnum;
  PaymentsWidgets: ResolverTypeWrapper<
    Omit<PaymentsWidgets, 'widgets'> & { widgets: Array<ResolversTypes['Widget']> }
  >;
  Payout: ResolverTypeWrapper<
    Omit<Payout, 'workflow'> & { workflow?: Maybe<ResolversTypes['PayoutWorkflow']> }
  >;
  PayoutApproveBulkResponse:
    | ResolversTypes['PayoutApproveBulkResponseFailure']
    | ResolversTypes['PayoutApproveBulkResponseSuccess'];
  PayoutApproveBulkResponseFailure: ResolverTypeWrapper<PayoutApproveBulkResponseFailure>;
  PayoutApproveBulkResponseSuccess: ResolverTypeWrapper<PayoutApproveBulkResponseSuccess>;
  PayoutBatch: ResolverTypeWrapper<PayoutBatch>;
  PayoutBatchDates: ResolverTypeWrapper<PayoutBatchDates>;
  PayoutBatchPayoutsCount: ResolverTypeWrapper<PayoutBatchPayoutsCount>;
  PayoutBatchStatusEnum: PayoutBatchStatusEnum;
  PayoutBatchTypeEnum: PayoutBatchTypeEnum;
  PayoutBatchesResponse: ResolverTypeWrapper<PayoutBatchesResponse>;
  PayoutCompositeCreateResponse: ResolverTypeWrapper<PayoutCompositeCreateResponse>;
  PayoutCompositeMerchantContactInput: PayoutCompositeMerchantContactInput;
  PayoutCreateIciciResponse: ResolverTypeWrapper<PayoutCreateIciciResponse>;
  PayoutCreateResponse: ResolverTypeWrapper<PayoutCreateResponse>;
  PayoutDate: ResolverTypeWrapper<PayoutDate>;
  PayoutFee: ResolverTypeWrapper<PayoutFee>;
  PayoutFeeEnum: PayoutFeeEnum;
  PayoutInternalStatusEnum: PayoutInternalStatusEnum;
  PayoutLink: ResolverTypeWrapper<PayoutLink>;
  PayoutLinkCreateResponse: ResolverTypeWrapper<PayoutLinkCreateResponse>;
  PayoutLinkDate: ResolverTypeWrapper<PayoutLinkDate>;
  PayoutLinkSendVia: PayoutLinkSendVia;
  PayoutLinkSentVia: ResolverTypeWrapper<PayoutLinkSentVia>;
  PayoutLinkStatusEnum: PayoutLinkStatusEnum;
  PayoutLinksResponse: ResolverTypeWrapper<PayoutLinksResponse>;
  PayoutModeEnum: PayoutModeEnum;
  PayoutPendingOnInput: PayoutPendingOnInput;
  PayoutPendingOnRoleEnum: PayoutPendingOnRoleEnum;
  PayoutPurpose: ResolverTypeWrapper<PayoutPurpose>;
  PayoutPurposeCreateResponse: ResolverTypeWrapper<PayoutPurposeCreateResponse>;
  PayoutPurposeTypeEnum: PayoutPurposeTypeEnum;
  PayoutRejectBulkResponse:
    | ResolversTypes['PayoutRejectBulkResponseFailure']
    | ResolversTypes['PayoutRejectBulkResponseSuccess'];
  PayoutRejectBulkResponseFailure: ResolverTypeWrapper<PayoutRejectBulkResponseFailure>;
  PayoutRejectBulkResponseSuccess: ResolverTypeWrapper<PayoutRejectBulkResponseSuccess>;
  PayoutSource: ResolverTypeWrapper<PayoutSource>;
  PayoutStatusEnum: PayoutStatusEnum;
  PayoutWorkflow: ResolversTypes['PayoutWorkflowHistory'] | ResolversTypes['Workflow'];
  PayoutWorkflowHistory: ResolverTypeWrapper<PayoutWorkflowHistory>;
  PayoutWorkflowRole: ResolverTypeWrapper<PayoutWorkflowRole>;
  PayoutWorkflowRoleChecker: ResolverTypeWrapper<PayoutWorkflowRoleChecker>;
  PayoutWorkflowRoleTypeEnum: PayoutWorkflowRoleTypeEnum;
  PayoutWorkflowStep: ResolverTypeWrapper<PayoutWorkflowStep>;
  PayoutWorkflowStepOperationTypeEnum: PayoutWorkflowStepOperationTypeEnum;
  PayoutsPendingSummary: ResolverTypeWrapper<PayoutsPendingSummary>;
  PayoutsQueuedReasonEnum: PayoutsQueuedReasonEnum;
  PayoutsQueuedSummary:
    | ResolversTypes['PayoutsQueuedSummaryBeneficiaryBankDown']
    | ResolversTypes['PayoutsQueuedSummaryLowBalance']
    | ResolversTypes['PayoutsQueuedSummaryNEFTLimitExhausted']
    | ResolversTypes['PayoutsQueuedSummaryNEFTWindowClosed']
    | ResolversTypes['PayoutsQueuedSummaryNPCISystemDown']
    | ResolversTypes['PayoutsQueuedSummaryWithoutReason'];
  PayoutsQueuedSummaryBeneficiaryBankDown: ResolverTypeWrapper<PayoutsQueuedSummaryBeneficiaryBankDown>;
  PayoutsQueuedSummaryLowBalance: ResolverTypeWrapper<PayoutsQueuedSummaryLowBalance>;
  PayoutsQueuedSummaryNEFTLimitExhausted: ResolverTypeWrapper<PayoutsQueuedSummaryNeftLimitExhausted>;
  PayoutsQueuedSummaryNEFTWindowClosed: ResolverTypeWrapper<PayoutsQueuedSummaryNeftWindowClosed>;
  PayoutsQueuedSummaryNPCISystemDown: ResolverTypeWrapper<PayoutsQueuedSummaryNpciSystemDown>;
  PayoutsQueuedSummaryWithoutReason: ResolverTypeWrapper<PayoutsQueuedSummaryWithoutReason>;
  PayoutsResponse: ResolverTypeWrapper<PayoutsResponse>;
  PayoutsScheduledPeriodEnum: PayoutsScheduledPeriodEnum;
  PayoutsScheduledSummary:
    | ResolversTypes['PayoutsScheduledSummaryAllTime']
    | ResolversTypes['PayoutsScheduledSummaryNextMonth']
    | ResolversTypes['PayoutsScheduledSummaryNextTwoDays']
    | ResolversTypes['PayoutsScheduledSummaryNextWeek']
    | ResolversTypes['PayoutsScheduledSummaryToday'];
  PayoutsScheduledSummaryAllTime: ResolverTypeWrapper<PayoutsScheduledSummaryAllTime>;
  PayoutsScheduledSummaryNextMonth: ResolverTypeWrapper<PayoutsScheduledSummaryNextMonth>;
  PayoutsScheduledSummaryNextTwoDays: ResolverTypeWrapper<PayoutsScheduledSummaryNextTwoDays>;
  PayoutsScheduledSummaryNextWeek: ResolverTypeWrapper<PayoutsScheduledSummaryNextWeek>;
  PayoutsScheduledSummaryToday: ResolverTypeWrapper<PayoutsScheduledSummaryToday>;
  PayoutsSummary: ResolverTypeWrapper<
    Omit<PayoutsSummary, 'queued' | 'scheduled'> & {
      queued: Array<ResolversTypes['PayoutsQueuedSummary']>;
      scheduled: Array<ResolversTypes['PayoutsScheduledSummary']>;
    }
  >;
  Phone: ResolverTypeWrapper<Phone>;
  PhoneInput: PhoneInput;
  PlatformEnum: PlatformEnum;
  PointOfSale: ResolverTypeWrapper<PointOfSale>;
  PointOfSaleKeyFetchResponse: ResolverTypeWrapper<PointOfSaleKeyFetchResponse>;
  PointOfSalePaymentCreateFailureResponse: ResolverTypeWrapper<PointOfSalePaymentCreateFailureResponse>;
  PointOfSalePaymentCreateResponse:
    | ResolversTypes['PointOfSalePaymentCreateFailureResponse']
    | ResolversTypes['PointOfSalePaymentCreateSuccessResponse'];
  PointOfSalePaymentCreateSuccessResponse: ResolverTypeWrapper<PointOfSalePaymentCreateSuccessResponse>;
  PointOfSalePaymentTransactionInput: PointOfSalePaymentTransactionInput;
  PointOfSalePaymentTransactionStatus: PointOfSalePaymentTransactionStatus;
  PointOfSalePaymentUpdateFailureResponse: ResolverTypeWrapper<PointOfSalePaymentUpdateFailureResponse>;
  PointOfSalePaymentUpdateResponse:
    | ResolversTypes['PointOfSalePaymentUpdateFailureResponse']
    | ResolversTypes['PointOfSalePaymentUpdateSuccessResponse'];
  PointOfSalePaymentUpdateSuccessResponse: ResolverTypeWrapper<PointOfSalePaymentUpdateSuccessResponse>;
  PositiveInt: ResolverTypeWrapper<Scalars['PositiveInt']>;
  ProductTypeEnum: ProductTypeEnum;
  QRCode: ResolverTypeWrapper<QrCode>;
  QRCodeCreateFailureResponse: ResolverTypeWrapper<QrCodeCreateFailureResponse>;
  QRCodeCreateResponse:
    | ResolversTypes['QRCodeCreateFailureResponse']
    | ResolversTypes['QRCodeCreateSuccessResponse'];
  QRCodeCreateSuccessResponse: ResolverTypeWrapper<QrCodeCreateSuccessResponse>;
  QRCodeDate: ResolverTypeWrapper<QrCodeDate>;
  QRCodePaymentDetail: ResolverTypeWrapper<QrCodePaymentDetail>;
  QRCodeStatusEnum: QrCodeStatusEnum;
  QRCodeTypeEnum: QrCodeTypeEnum;
  QRCodeUsageEnum: QrCodeUsageEnum;
  QRCodesResponse: ResolverTypeWrapper<QrCodesResponse>;
  Query: ResolverTypeWrapper<{}>;
  RecentTransactionsWidget: ResolverTypeWrapper<RecentTransactionsWidget>;
  RefreshAccessToken: ResolverTypeWrapper<RefreshAccessToken>;
  RefundsResponse: ResolverTypeWrapper<RefundsResponse>;
  RegisterBusinessResponse: ResolverTypeWrapper<RegisterBusinessResponse>;
  RegisterEmail: ResolversTypes['RegisterEmailError'] | ResolversTypes['RegisterEmailSuccess'];
  RegisterEmailError: ResolverTypeWrapper<RegisterEmailError>;
  RegisterEmailSuccess: ResolverTypeWrapper<RegisterEmailSuccess>;
  RegisterEmailVerificationMethodEnum: RegisterEmailVerificationMethodEnum;
  RegisterEmailVerifyResponse:
    | ResolversTypes['RegisterEmailVerifyResponseFailure']
    | ResolversTypes['RegisterEmailVerifyResponseSuccess'];
  RegisterEmailVerifyResponseFailure: ResolverTypeWrapper<RegisterEmailVerifyResponseFailure>;
  RegisterEmailVerifyResponseSuccess: ResolverTypeWrapper<RegisterEmailVerifyResponseSuccess>;
  RegisterFCMTokenResponse: ResolverTypeWrapper<RegisterFcmTokenResponse>;
  RegisterMerchantErrorEnum: RegisterMerchantErrorEnum;
  RegisterMerchantResponse:
    | ResolversTypes['RegisterMerchantResponseFailure']
    | ResolversTypes['RegisterMerchantResponseSuccess'];
  RegisterMerchantResponseFailure: ResolverTypeWrapper<RegisterMerchantResponseFailure>;
  RegisterMerchantResponseSuccess: ResolverTypeWrapper<RegisterMerchantResponseSuccess>;
  RegisterMobileVerifyEnum: RegisterMobileVerifyEnum;
  RegisterMobileVerifyResponse:
    | ResolversTypes['RegisterMobileVerifyResponseFailure']
    | ResolversTypes['RegisterMobileVerifyResponseSuccess'];
  RegisterMobileVerifyResponseFailure: ResolverTypeWrapper<RegisterMobileVerifyResponseFailure>;
  RegisterMobileVerifyResponseSuccess: ResolverTypeWrapper<RegisterMobileVerifyResponseSuccess>;
  RegisterOAuth:
    | ResolversTypes['AuthUser']
    | ResolversTypes['RegisterOAuthEmailError']
    | ResolversTypes['RegisterOAuthEmailExist']
    | ResolversTypes['RegisterOAuthInvalidTokenError'];
  RegisterOAuthEmailError: ResolverTypeWrapper<RegisterOAuthEmailError>;
  RegisterOAuthEmailExist: ResolverTypeWrapper<RegisterOAuthEmailExist>;
  RegisterOAuthInvalidTokenError: ResolverTypeWrapper<RegisterOAuthInvalidTokenError>;
  RejectPayoutBatchResponse:
    | ResolversTypes['RejectPayoutBatchResponseFailure']
    | ResolversTypes['RejectPayoutBatchResponseSuccess'];
  RejectPayoutBatchResponseFailure: ResolverTypeWrapper<RejectPayoutBatchResponseFailure>;
  RejectPayoutBatchResponseSuccess: ResolverTypeWrapper<RejectPayoutBatchResponseSuccess>;
  RejectPayoutResponse: ResolverTypeWrapper<RejectPayoutResponse>;
  ResendEmailOtp: ResolverTypeWrapper<ResendEmailOtp>;
  ResendTwoFactorLoginOtpResponse: ResolverTypeWrapper<ResendTwoFactorLoginOtpResponse>;
  ResetPasswordEmail: ResolverTypeWrapper<ResetPasswordEmail>;
  SendApprovePayoutBatchOtp: ResolverTypeWrapper<SendApprovePayoutBatchOtp>;
  SendApprovePayoutOtp: ResolverTypeWrapper<SendApprovePayoutOtp>;
  SendCreatePayoutLinkOtp: ResolverTypeWrapper<SendCreatePayoutLinkOtp>;
  SendCreatePayoutOtp: ResolverTypeWrapper<SendCreatePayoutOtp>;
  SendIciciPayoutOtpResponse: ResolverTypeWrapper<SendIciciPayoutOtpResponse>;
  SendPayoutApproveBulkOtp: ResolverTypeWrapper<SendPayoutApproveBulkOtp>;
  SendPayoutCompositeOtp: ResolverTypeWrapper<SendPayoutCompositeOtp>;
  Settlement: ResolverTypeWrapper<Settlement>;
  SettlementAmount: ResolverTypeWrapper<SettlementAmount>;
  SettlementBreakup: ResolverTypeWrapper<SettlementBreakup>;
  SettlementBreakupComponentEnum: SettlementBreakupComponentEnum;
  SettlementBreakupTransactionTypeEnum: SettlementBreakupTransactionTypeEnum;
  SettlementCycle: ResolverTypeWrapper<SettlementCycle>;
  SettlementStatusEnum: SettlementStatusEnum;
  SettlementsResponse: ResolverTypeWrapper<SettlementsResponse>;
  SettlementsWidget: ResolverTypeWrapper<SettlementsWidget>;
  SmsNotificationStatusResponse: ResolverTypeWrapper<SmsNotificationStatusResponse>;
  SmsNotificationToggle: ResolverTypeWrapper<SmsNotificationToggle>;
  SortByEnum: SortByEnum;
  SpeedProcessedEnum: SpeedProcessedEnum;
  String: ResolverTypeWrapper<Scalars['String']>;
  TDSCategory: ResolverTypeWrapper<TdsCategory>;
  Transaction: ResolverTypeWrapper<
    Omit<Transaction, 'source'> & { source: ResolversTypes['TransactionSource'] }
  >;
  TransactionAmount: ResolverTypeWrapper<TransactionAmount>;
  TransactionError: ResolverTypeWrapper<TransactionError>;
  TransactionSource:
    | ResolversTypes['Payout']
    | ResolversTypes['TransactionSourceAdjustment']
    | ResolversTypes['TransactionSourceBankTransfer']
    | ResolversTypes['TransactionSourceExternal']
    | ResolversTypes['TransactionSourceFundAccountValidation']
    | ResolversTypes['TransactionSourceReversal'];
  TransactionSourceAdjustment: ResolverTypeWrapper<TransactionSourceAdjustment>;
  TransactionSourceBankTransfer: ResolverTypeWrapper<TransactionSourceBankTransfer>;
  TransactionSourceBankTransferModeEnum: TransactionSourceBankTransferModeEnum;
  TransactionSourceBankTransferPayee: ResolverTypeWrapper<TransactionSourceBankTransferPayee>;
  TransactionSourceBankTransferPayer: ResolverTypeWrapper<TransactionSourceBankTransferPayer>;
  TransactionSourceDetails: ResolverTypeWrapper<TransactionSourceDetails>;
  TransactionSourceExternal: ResolverTypeWrapper<TransactionSourceExternal>;
  TransactionSourceFundAccountValidation: ResolverTypeWrapper<TransactionSourceFundAccountValidation>;
  TransactionSourceReversal: ResolverTypeWrapper<TransactionSourceReversal>;
  TransactionSourceTypeEnum: TransactionSourceTypeEnum;
  TransactionStatus: ResolverTypeWrapper<TransactionStatus>;
  TransactionTypeEnum: TransactionTypeEnum;
  TransactionsResponse: ResolverTypeWrapper<TransactionsResponse>;
  TwoFactorActionTypeEnum: TwoFactorActionTypeEnum;
  TwoFactorAddMobileOtpErrorResponse: ResolverTypeWrapper<TwoFactorAddMobileOtpErrorResponse>;
  TwoFactorAddMobileOtpResponse:
    | ResolversTypes['TwoFactorAddMobileOtpErrorResponse']
    | ResolversTypes['TwoFactorAddMobileOtpSuccessResponse'];
  TwoFactorAddMobileOtpSuccessResponse: ResolverTypeWrapper<TwoFactorAddMobileOtpSuccessResponse>;
  TwoFactorAddMobileOtpVerifyErrorResponse: ResolverTypeWrapper<TwoFactorAddMobileOtpVerifyErrorResponse>;
  TwoFactorAddMobileOtpVerifyResponse:
    | ResolversTypes['TwoFactorAddMobileOtpVerifyErrorResponse']
    | ResolversTypes['TwoFactorAddMobileOtpVerifySuccessResponse'];
  TwoFactorAddMobileOtpVerifySuccessResponse: ResolverTypeWrapper<TwoFactorAddMobileOtpVerifySuccessResponse>;
  TwoFactorAuthUpdateFailureResponse: ResolverTypeWrapper<TwoFactorAuthUpdateFailureResponse>;
  TwoFactorAuthUpdateResponse:
    | ResolversTypes['TwoFactorAuthUpdateFailureResponse']
    | ResolversTypes['TwoFactorAuthUpdateSuccessResponse'];
  TwoFactorAuthUpdateSuccessResponse: ResolverTypeWrapper<TwoFactorAuthUpdateSuccessResponse>;
  TwoFactorEmailOtpVerifyFailureResponse: ResolverTypeWrapper<TwoFactorEmailOtpVerifyFailureResponse>;
  TwoFactorEmailOtpVerifyResponse:
    | ResolversTypes['TwoFactorEmailOtpVerifyFailureResponse']
    | ResolversTypes['TwoFactorEmailOtpVerifySuccessResponse'];
  TwoFactorEmailOtpVerifySuccessResponse: ResolverTypeWrapper<TwoFactorEmailOtpVerifySuccessResponse>;
  TwoFactorOtpFailureResponse: ResolverTypeWrapper<TwoFactorOtpFailureResponse>;
  TwoFactorOtpMediumEnum: TwoFactorOtpMediumEnum;
  TwoFactorOtpResponse:
    | ResolversTypes['TwoFactorOtpFailureResponse']
    | ResolversTypes['TwoFactorOtpSuccessResponse'];
  TwoFactorOtpSuccessResponse: ResolverTypeWrapper<TwoFactorOtpSuccessResponse>;
  TwoFactorPasswordCreateErrorResponse: ResolverTypeWrapper<TwoFactorPasswordCreateErrorResponse>;
  TwoFactorPasswordCreateResponse:
    | ResolversTypes['TwoFactorPasswordCreateErrorResponse']
    | ResolversTypes['TwoFactorPasswordCreateSuccessResponse'];
  TwoFactorPasswordCreateSuccessResponse: ResolverTypeWrapper<TwoFactorPasswordCreateSuccessResponse>;
  TwoFactorPasswordEnabledErrorResponse: ResolverTypeWrapper<TwoFactorPasswordEnabledErrorResponse>;
  TwoFactorPasswordEnabledResponse:
    | ResolversTypes['TwoFactorPasswordEnabledErrorResponse']
    | ResolversTypes['TwoFactorPasswordEnabledSuccessResponse'];
  TwoFactorPasswordEnabledSuccessResponse: ResolverTypeWrapper<TwoFactorPasswordEnabledSuccessResponse>;
  TwoFactorUnverifiedMobileVerifyResponse: ResolverTypeWrapper<TwoFactorUnverifiedMobileVerifyResponse>;
  URL: ResolverTypeWrapper<Scalars['URL']>;
  UpdateMerchantConsentResponse: ResolverTypeWrapper<UpdateMerchantConsentResponse>;
  UpiTerminalProcurementStatusEnum: UpiTerminalProcurementStatusEnum;
  Upload: ResolverTypeWrapper<Scalars['Upload']>;
  User: ResolverTypeWrapper<User>;
  UserAcquisitionSourceEnum: UserAcquisitionSourceEnum;
  UserAuthentication: ResolverTypeWrapper<UserAuthentication>;
  UserContactDetails: ResolverTypeWrapper<UserContactDetails>;
  UserContactDetailsUpdateResponse: ResolverTypeWrapper<UserContactDetailsUpdateResponse>;
  UserDeviceAnalyticsResponse: ResolverTypeWrapper<UserDeviceAnalyticsResponse>;
  UserLogout: ResolverTypeWrapper<UserLogout>;
  UserOtpVerifyErrorTypeEnum: UserOtpVerifyErrorTypeEnum;
  UserOtpVerifyResponse: ResolverTypeWrapper<UserOtpVerifyResponse>;
  UserRole: ResolverTypeWrapper<UserRole>;
  UserRoleBankingEnum: UserRoleBankingEnum;
  UserRolePaymentsEnum: UserRolePaymentsEnum;
  UserSignupCampaignEnum: UserSignupCampaignEnum;
  VPA: ResolverTypeWrapper<Scalars['VPA']>;
  ValidateVpaFailureResponse: ResolverTypeWrapper<ValidateVpaFailureResponse>;
  ValidateVpaResponse:
    | ResolversTypes['ValidateVpaFailureResponse']
    | ResolversTypes['ValidateVpaSuccessResponse'];
  ValidateVpaSuccessResponse: ResolverTypeWrapper<ValidateVpaSuccessResponse>;
  VendorPayment: ResolverTypeWrapper<VendorPayment>;
  VendorPaymentCancelResponse: ResolverTypeWrapper<VendorPaymentCancelResponse>;
  VendorPaymentDates: ResolverTypeWrapper<VendorPaymentDates>;
  VendorPaymentGST: ResolverTypeWrapper<VendorPaymentGst>;
  VendorPaymentInvoice: ResolverTypeWrapper<VendorPaymentInvoice>;
  VendorPaymentInvoiceAttachment: ResolverTypeWrapper<VendorPaymentInvoiceAttachment>;
  VendorPaymentPayoutAmounts: ResolverTypeWrapper<VendorPaymentPayoutAmounts>;
  VendorPaymentPayoutCreateResponse: ResolverTypeWrapper<VendorPaymentPayoutCreateResponse>;
  VendorPaymentStatusEnum: VendorPaymentStatusEnum;
  VendorPaymentTDS: ResolverTypeWrapper<VendorPaymentTds>;
  VendorPaymentsResponse: ResolverTypeWrapper<VendorPaymentsResponse>;
  WhatsappNotificationStatusResponse: ResolverTypeWrapper<WhatsappNotificationStatusResponse>;
  WhatsappNotificationToggle: ResolverTypeWrapper<WhatsappNotificationToggle>;
  Widget:
    | ResolversTypes['AcceptPaymentsWidget']
    | ResolversTypes['OnboardingWidget']
    | ResolversTypes['PaymentAnalyticsWidget']
    | ResolversTypes['PaymentHandleWidget']
    | ResolversTypes['PaymentsWidgetError']
    | ResolversTypes['RecentTransactionsWidget']
    | ResolversTypes['SettlementsWidget'];
  WidgetVariantEnum: WidgetVariantEnum;
  Workflow: ResolverTypeWrapper<Workflow>;
  WorkflowConfig: ResolverTypeWrapper<WorkflowConfig>;
  WorkflowConfigState: ResolverTypeWrapper<
    Omit<WorkflowConfigState, 'rule'> & { rule: ResolversTypes['WorkflowConfigStateRulePayout'] }
  >;
  WorkflowConfigStateRulePayout:
    | ResolversTypes['WorkflowConfigStateRulePayoutTypeBetween']
    | ResolversTypes['WorkflowConfigStateRulePayoutTypeChecker']
    | ResolversTypes['WorkflowConfigStateRulePayoutTypeMergeStates'];
  WorkflowConfigStateRulePayoutTypeBetween: ResolverTypeWrapper<WorkflowConfigStateRulePayoutTypeBetween>;
  WorkflowConfigStateRulePayoutTypeChecker: ResolverTypeWrapper<WorkflowConfigStateRulePayoutTypeChecker>;
  WorkflowConfigStateRulePayoutTypeMergeStates: ResolverTypeWrapper<WorkflowConfigStateRulePayoutTypeMergeStates>;
  WorkflowConfigStateTransition: ResolverTypeWrapper<WorkflowConfigStateTransition>;
  WorkflowConfigStateTypeEnum: WorkflowConfigStateTypeEnum;
  WorkflowConfigTemplate: ResolverTypeWrapper<WorkflowConfigTemplate>;
  WorkflowConfigTemplateTypeEnum: WorkflowConfigTemplateTypeEnum;
  WorkflowCreator: ResolverTypeWrapper<WorkflowCreator>;
  WorkflowCreatorTypeEnum: WorkflowCreatorTypeEnum;
  WorkflowState: ResolverTypeWrapper<
    Omit<WorkflowState, 'rule'> & { rule: ResolversTypes['WorkflowConfigStateRulePayout'] }
  >;
  WorkflowStateAction: ResolverTypeWrapper<WorkflowStateAction>;
  WorkflowStateActionActor: ResolverTypeWrapper<WorkflowStateActionActor>;
  WorkflowStateActionStatusEnum: WorkflowStateActionStatusEnum;
  WorkflowStateDates: ResolverTypeWrapper<WorkflowStateDates>;
  WorkflowStateStatusEnum: WorkflowStateStatusEnum;
  WorkflowStatusEnum: WorkflowStatusEnum;
  merchantConfigurationUpdateResponse: ResolverTypeWrapper<MerchantConfigurationUpdateResponse>;
  userOtpResponse: ResolverTypeWrapper<UserOtpResponse>;
};

/** Mapping between all available schema types and the resolvers parents */
export type ResolversParentTypes = {
  AadhaarCaptchaResponse: AadhaarCaptchaResponse;
  AadhaarCaptchaV2FailureResponse: AadhaarCaptchaV2FailureResponse;
  AadhaarCaptchaV2Response:
    | ResolversParentTypes['AadhaarCaptchaV2FailureResponse']
    | ResolversParentTypes['AadhaarCaptchaV2SuccessResponse'];
  AadhaarCaptchaV2SuccessResponse: AadhaarCaptchaV2SuccessResponse;
  AadhaarCaptchaVerifyResponse: AadhaarCaptchaVerifyResponse;
  AadhaarDigilockerOtpFailureResponse: AadhaarDigilockerOtpFailureResponse;
  AadhaarDigilockerOtpResponse:
    | ResolversParentTypes['AadhaarDigilockerOtpFailureResponse']
    | ResolversParentTypes['AadhaarDigilockerOtpSuccessResponse'];
  AadhaarDigilockerOtpSuccessResponse: AadhaarDigilockerOtpSuccessResponse;
  AadhaarDigilockerOtpVerifyFailureResponse: AadhaarDigilockerOtpVerifyFailureResponse;
  AadhaarDigilockerOtpVerifyResponse:
    | ResolversParentTypes['AadhaarDigilockerOtpVerifyFailureResponse']
    | ResolversParentTypes['AadhaarDigilockerOtpVerifySuccessResponse'];
  AadhaarDigilockerOtpVerifySuccessResponse: AadhaarDigilockerOtpVerifySuccessResponse;
  AadhaarDigilockerRedirectionUrlFailureResponse: AadhaarDigilockerRedirectionUrlFailureResponse;
  AadhaarDigilockerRedirectionUrlResponse:
    | ResolversParentTypes['AadhaarDigilockerRedirectionUrlFailureResponse']
    | ResolversParentTypes['AadhaarDigilockerRedirectionUrlSuccessResponse'];
  AadhaarDigilockerRedirectionUrlSuccessResponse: AadhaarDigilockerRedirectionUrlSuccessResponse;
  AadhaarDigilockerRedirectionUrlVerifyResponse:
    | ResolversParentTypes['AadhaarDigilockerRedirectionVerificationFailureResponse']
    | ResolversParentTypes['AadhaarDigilockerRedirectionVerificationSuccessResponse'];
  AadhaarDigilockerRedirectionVerificationFailureResponse: AadhaarDigilockerRedirectionVerificationFailureResponse;
  AadhaarDigilockerRedirectionVerificationSuccessResponse: AadhaarDigilockerRedirectionVerificationSuccessResponse;
  AadhaarOtpVerifyResponse: AadhaarOtpVerifyResponse;
  AcceptPaymentsProduct: AcceptPaymentsProduct;
  AcceptPaymentsWidget: AcceptPaymentsWidget;
  AccountVerificationOtpResendResponse: AccountVerificationOtpResendResponse;
  AccountVerificationOtpResponse: AccountVerificationOtpResponse;
  AcquirerData: AcquirerData;
  Address: Address;
  AddressByPincodeFailureResponse: AddressByPincodeFailureResponse;
  AddressByPincodeResponse:
    | ResolversParentTypes['AddressByPincodeFailureResponse']
    | ResolversParentTypes['AddressByPincodeSuccessResponse'];
  AddressByPincodeSuccessResponse: AddressByPincodeSuccessResponse;
  AggregationResultType: AggregationResultType;
  ApproveIciciPayoutResponse: ApproveIciciPayoutResponse;
  ApprovePayoutBatchResponse:
    | ResolversParentTypes['ApprovePayoutBatchResponseFailure']
    | ResolversParentTypes['ApprovePayoutBatchResponseSuccess'];
  ApprovePayoutBatchResponseFailure: ApprovePayoutBatchResponseFailure;
  ApprovePayoutBatchResponseSuccess: ApprovePayoutBatchResponseSuccess;
  ApprovePayoutResponse: ApprovePayoutResponse;
  Auth:
    | ResolversParentTypes['AuthUnauthenticated']
    | ResolversParentTypes['AuthUnregistered']
    | ResolversParentTypes['AuthUser'];
  AuthUnauthenticated: AuthUnauthenticated;
  AuthUnregistered: AuthUnregistered;
  AuthUser: AuthUser;
  Bank: Bank;
  BigInt: Scalars['BigInt'];
  Boolean: Scalars['Boolean'];
  BusinessType: BusinessType;
  CheckoutOptions: CheckoutOptions;
  ClarificationComment: ClarificationComment;
  ClarificationComments: ClarificationComments;
  ConfigData: ConfigData;
  CouponApplyResponse: CouponApplyResponse;
  CouponValidateResponse: CouponValidateResponse;
  Currency: Currency;
  CurrencyInput: CurrencyInput;
  Customer: Customer;
  CustomerAddress: CustomerAddress;
  CustomerInput: CustomerInput;
  DateTime: Scalars['DateTime'];
  DeregisterFCMTokenResponse: DeregisterFcmTokenResponse;
  DeviceAnalyticsDataInput: DeviceAnalyticsDataInput;
  EmailAddress: Scalars['EmailAddress'];
  FailedPaymentsOverviewFailureResponse: FailedPaymentsOverviewFailureResponse;
  FailedPaymentsOverviewResponse:
    | ResolversParentTypes['FailedPaymentsOverviewFailureResponse']
    | ResolversParentTypes['FailedPaymentsOverviewSuccessResponse'];
  FailedPaymentsOverviewSuccessResponse: FailedPaymentsOverviewSuccessResponse;
  FieldDetailsInput: FieldDetailsInput;
  Float: Scalars['Float'];
  GoalTrackerMetaData: GoalTrackerMetaData;
  GoalTrackerSettings: GoalTrackerSettings;
  ID: Scalars['ID'];
  Image: Image;
  Int: Scalars['Int'];
  Invoice: Invoice;
  InvoiceAmount: InvoiceAmount;
  InvoiceDate: InvoiceDate;
  InvoiceItem: InvoiceItem;
  InvoicesResponse: InvoicesResponse;
  JSON: Scalars['JSON'];
  JSONObject: Scalars['JSONObject'];
  LoginOtpError: LoginOtpError;
  LoginOtpResendError: LoginOtpResendError;
  LoginOtpResendResponse:
    | ResolversParentTypes['LoginOtpResendError']
    | ResolversParentTypes['LoginOtpResendSuccess'];
  LoginOtpResendSuccess: LoginOtpResendSuccess;
  LoginOtpResponse: ResolversParentTypes['LoginOtpError'] | ResolversParentTypes['LoginOtpSuccess'];
  LoginOtpSuccess: LoginOtpSuccess;
  Merchant: Merchant;
  MerchantAcceptanceChannel: MerchantAcceptanceChannel;
  MerchantAcceptanceChannelInput: MerchantAcceptanceChannelInput;
  MerchantAcceptanceChannelWhatsappSmsEmail: MerchantAcceptanceChannelWhatsappSmsEmail;
  MerchantAcceptanceChannelWhatsappSmsEmailInput: MerchantAcceptanceChannelWhatsappSmsEmailInput;
  MerchantActivation: Omit<MerchantActivation, 'merchantEscalations'> & {
    merchantEscalations: ResolversParentTypes['MerchantEscalations'];
  };
  MerchantActivationDedupe: MerchantActivationDedupe;
  MerchantActivationEscalationsBreached: MerchantActivationEscalationsBreached;
  MerchantActivationEscalationsNotBreached: MerchantActivationEscalationsNotBreached;
  MerchantActivationFlow: MerchantActivationFlow;
  MerchantActivationResponse: MerchantActivationResponse;
  MerchantAddress: MerchantAddress;
  MerchantAddressInput: MerchantAddressInput;
  MerchantAnalytics: MerchantAnalytics;
  MerchantApiKey: MerchantApiKey;
  MerchantApiKeyCreateResponse: MerchantApiKeyCreateResponse;
  MerchantApiKeyInterface:
    | ResolversParentTypes['MerchantApiKey']
    | ResolversParentTypes['MerchantApiKeyCreateResponse']
    | ResolversParentTypes['MerchantApiKeyRegenerateNew']
    | ResolversParentTypes['MerchantApiKeyRegenerateOld']
    | ResolversParentTypes['MerchantApiKeysCreateSuccess'];
  MerchantApiKeyRegenerateNew: MerchantApiKeyRegenerateNew;
  MerchantApiKeyRegenerateOld: MerchantApiKeyRegenerateOld;
  MerchantApiKeyRegenerateResponse: MerchantApiKeyRegenerateResponse;
  MerchantApiKeysCreateFailure: MerchantApiKeysCreateFailure;
  MerchantApiKeysCreateResponse:
    | ResolversParentTypes['MerchantApiKeysCreateFailure']
    | ResolversParentTypes['MerchantApiKeysCreateSuccess'];
  MerchantApiKeysCreateSuccess: MerchantApiKeysCreateSuccess;
  MerchantAverageOrderField: MerchantAverageOrderField;
  MerchantAverageOrderFieldValue: MerchantAverageOrderFieldValue;
  MerchantBalance: MerchantBalance;
  MerchantBank: MerchantBank;
  MerchantBankAccountDetails: MerchantBankAccountDetails;
  MerchantBankAccountDocumentUploadFailureResponse: MerchantBankAccountDocumentUploadFailureResponse;
  MerchantBankAccountDocumentUploadResponse:
    | ResolversParentTypes['MerchantBankAccountDocumentUploadFailureResponse']
    | ResolversParentTypes['MerchantBankAccountDocumentUploadSuccessResponse'];
  MerchantBankAccountDocumentUploadSuccessResponse: MerchantBankAccountDocumentUploadSuccessResponse;
  MerchantBankAccountUpdateFailureResponse: MerchantBankAccountUpdateFailureResponse;
  MerchantBankAccountUpdateResponse:
    | ResolversParentTypes['MerchantBankAccountUpdateFailureResponse']
    | ResolversParentTypes['MerchantBankAccountUpdateSuccessResponse'];
  MerchantBankAccountUpdateSuccessResponse: MerchantBankAccountUpdateSuccessResponse;
  MerchantBankDetails: MerchantBankDetails;
  MerchantBankDetailsFailureResponse: MerchantBankDetailsFailureResponse;
  MerchantBankDetailsResponse:
    | ResolversParentTypes['MerchantBankDetailsFailureResponse']
    | ResolversParentTypes['MerchantBankDetailsSuccessResponse'];
  MerchantBankDetailsSuccessResponse: MerchantBankDetailsSuccessResponse;
  MerchantBankInput: MerchantBankInput;
  MerchantBankingAccount: MerchantBankingAccount;
  MerchantBankingAccountBalance: MerchantBankingAccountBalance;
  MerchantBankingAccountsBalanceResponse: MerchantBankingAccountsBalanceResponse;
  MerchantBankingRole: MerchantBankingRole;
  MerchantBusiness: MerchantBusiness;
  MerchantBusinessAddress: MerchantBusinessAddress;
  MerchantBusinessAddressInput: MerchantBusinessAddressInput;
  MerchantBusinessAppDetailsResponse: MerchantBusinessAppDetailsResponse;
  MerchantBusinessAppInput: MerchantBusinessAppInput;
  MerchantBusinessCategoriesResponse: MerchantBusinessCategoriesResponse;
  MerchantBusinessInput: MerchantBusinessInput;
  MerchantBusinessParentCategory: MerchantBusinessParentCategory;
  MerchantBusinessSubCategory: MerchantBusinessSubCategory;
  MerchantBusinessTypeField: MerchantBusinessTypeField;
  MerchantBusinessTypeInputField: MerchantBusinessTypeInputField;
  MerchantBusinessTypesResponse: MerchantBusinessTypesResponse;
  MerchantBusinessWebsiteDetailsResponse: MerchantBusinessWebsiteDetailsResponse;
  MerchantBusinessWebsiteInput: MerchantBusinessWebsiteInput;
  MerchantClarificationDetail: MerchantClarificationDetail;
  MerchantClarificationDetailsResponse: MerchantClarificationDetailsResponse;
  MerchantClarificationDetailsSubmitResponse: MerchantClarificationDetailsSubmitResponse;
  MerchantClarificationDetailsUpdateResponse: MerchantClarificationDetailsUpdateResponse;
  MerchantClarificationFieldValues: MerchantClarificationFieldValues;
  MerchantClarificationInputType: MerchantClarificationInputType;
  MerchantClarifications: MerchantClarifications;
  MerchantConfig:
    | ResolversParentTypes['MerchantConfigFailure']
    | ResolversParentTypes['MerchantOnboardingConfig'];
  MerchantConfigFailure: MerchantConfigFailure;
  MerchantConfigUpdateResponse: MerchantConfigUpdateResponse;
  MerchantConfiguration: MerchantConfiguration;
  MerchantConsentData: MerchantConsentData;
  MerchantConsentFailure: MerchantConsentFailure;
  MerchantConsentInput: MerchantConsentInput;
  MerchantConsentPayload: MerchantConsentPayload;
  MerchantConsentResponse:
    | ResolversParentTypes['MerchantConsentFailure']
    | ResolversParentTypes['MerchantConsentSuccess'];
  MerchantConsentSuccess: MerchantConsentSuccess;
  MerchantConsentsFailureResponse: MerchantConsentsFailureResponse;
  MerchantConsentsResponse:
    | ResolversParentTypes['MerchantConsentsFailureResponse']
    | ResolversParentTypes['MerchantConsentsSuccessResponse'];
  MerchantConsentsSuccessResponse: MerchantConsentsSuccessResponse;
  MerchantContact: MerchantContact;
  MerchantContactCreateResponse: MerchantContactCreateResponse;
  MerchantContactEmailOtpSendFailureResponse: MerchantContactEmailOtpSendFailureResponse;
  MerchantContactEmailOtpSendResponse:
    | ResolversParentTypes['MerchantContactEmailOtpSendFailureResponse']
    | ResolversParentTypes['MerchantContactEmailOtpSendSuccessResponse'];
  MerchantContactEmailOtpSendSuccessResponse: MerchantContactEmailOtpSendSuccessResponse;
  MerchantContactFundAccount: Omit<MerchantContactFundAccount, 'details'> & {
    details?: Maybe<ResolversParentTypes['MerchantContactFundAccountDetails']>;
  };
  MerchantContactFundAccountBankAccountInput: MerchantContactFundAccountBankAccountInput;
  MerchantContactFundAccountCreateResponse: MerchantContactFundAccountCreateResponse;
  MerchantContactFundAccountDetails:
    | ResolversParentTypes['MerchantContactFundAccountDetailsBankAccount']
    | ResolversParentTypes['MerchantContactFundAccountDetailsCard']
    | ResolversParentTypes['MerchantContactFundAccountDetailsVPA']
    | ResolversParentTypes['MerchantContactFundAccountDetailsWallet'];
  MerchantContactFundAccountDetailsBankAccount: MerchantContactFundAccountDetailsBankAccount;
  MerchantContactFundAccountDetailsCard: MerchantContactFundAccountDetailsCard;
  MerchantContactFundAccountDetailsVPA: MerchantContactFundAccountDetailsVpa;
  MerchantContactFundAccountDetailsWallet: MerchantContactFundAccountDetailsWallet;
  MerchantContactFundAccountVPAInput: MerchantContactFundAccountVpaInput;
  MerchantContactFundAccountsResponse: MerchantContactFundAccountsResponse;
  MerchantContactPerson: MerchantContactPerson;
  MerchantContactPersonInput: MerchantContactPersonInput;
  MerchantContactTypeCreateResponse:
    | ResolversParentTypes['MerchantContactTypeCreateResponseDuplicate']
    | ResolversParentTypes['MerchantContactTypeCreateResponseSuccess'];
  MerchantContactTypeCreateResponseDuplicate: MerchantContactTypeCreateResponseDuplicate;
  MerchantContactTypeCreateResponseSuccess: MerchantContactTypeCreateResponseSuccess;
  MerchantContactUpdateResponse: MerchantContactUpdateResponse;
  MerchantContactsResponse: MerchantContactsResponse;
  MerchantCreditBalance: MerchantCreditBalance;
  MerchantCreditBalanceFailureResponse: MerchantCreditBalanceFailureResponse;
  MerchantCreditBalanceResponse:
    | ResolversParentTypes['MerchantCreditBalanceFailureResponse']
    | ResolversParentTypes['MerchantCreditBalanceSuccessResponse'];
  MerchantCreditBalanceSuccessResponse: MerchantCreditBalanceSuccessResponse;
  MerchantDocument: MerchantDocument;
  MerchantDocumentByIdResponse: MerchantDocumentByIdResponse;
  MerchantDocumentField: MerchantDocumentField;
  MerchantDocumentFieldValue: MerchantDocumentFieldValue;
  MerchantDocumentFieldValueInterface:
    | ResolversParentTypes['MerchantDocumentByIdResponse']
    | ResolversParentTypes['MerchantDocumentFieldValue'];
  MerchantDocumentInput: MerchantDocumentInput;
  MerchantDocumentInputField: MerchantDocumentInputField;
  MerchantDocumentUpload: MerchantDocumentUpload;
  MerchantDocumentUploadFailureResponse: MerchantDocumentUploadFailureResponse;
  MerchantDocumentUploadResponse:
    | ResolversParentTypes['MerchantDocumentUploadFailureResponse']
    | ResolversParentTypes['MerchantDocumentUploadSuccessResponse'];
  MerchantDocumentUploadSuccessResponse: MerchantDocumentUploadSuccessResponse;
  MerchantEmailField: MerchantEmailField;
  MerchantEmailInputField: MerchantEmailInputField;
  MerchantEmailUpdateFailureResponse: MerchantEmailUpdateFailureResponse;
  MerchantEmailUpdateResponse:
    | ResolversParentTypes['MerchantEmailUpdateFailureResponse']
    | ResolversParentTypes['MerchantEmailUpdateSuccessResponse'];
  MerchantEmailUpdateSuccessResponse: MerchantEmailUpdateSuccessResponse;
  MerchantEscalationAction: MerchantEscalationAction;
  MerchantEscalationLimit: MerchantEscalationLimit;
  MerchantEscalations:
    | ResolversParentTypes['MerchantActivationEscalationsBreached']
    | ResolversParentTypes['MerchantActivationEscalationsNotBreached'];
  MerchantFeatureFlag: MerchantFeatureFlag;
  MerchantFeeBasedGating: MerchantFeeBasedGating;
  MerchantFieldClarificationReason: MerchantFieldClarificationReason;
  MerchantFieldInterface:
    | ResolversParentTypes['MerchantAverageOrderField']
    | ResolversParentTypes['MerchantBusinessTypeField']
    | ResolversParentTypes['MerchantDocumentField']
    | ResolversParentTypes['MerchantEmailField']
    | ResolversParentTypes['MerchantNumberField']
    | ResolversParentTypes['MerchantPhoneField']
    | ResolversParentTypes['MerchantStringField']
    | ResolversParentTypes['MerchantURLField'];
  MerchantGstinUpdate: MerchantGstinUpdate;
  MerchantGstinUpdateAsyncFlowSuccessResponse: MerchantGstinUpdateAsyncFlowSuccessResponse;
  MerchantGstinUpdateFailureResponse: MerchantGstinUpdateFailureResponse;
  MerchantGstinUpdateInSyncFlowResponse: MerchantGstinUpdateInSyncFlowResponse;
  MerchantGstinUpdateInSyncWorkFlowCreatedResponse: MerchantGstinUpdateInSyncWorkFlowCreatedResponse;
  MerchantGstinUpdateResponse:
    | ResolversParentTypes['MerchantGstinUpdateAsyncFlowSuccessResponse']
    | ResolversParentTypes['MerchantGstinUpdateFailureResponse']
    | ResolversParentTypes['MerchantGstinUpdateInSyncFlowResponse']
    | ResolversParentTypes['MerchantGstinUpdateInSyncWorkFlowCreatedResponse'];
  MerchantIdentityResponse: MerchantIdentityResponse;
  MerchantKYCPartnerAccessResponse: MerchantKycPartnerAccessResponse;
  MerchantKYCPartnerAccessStatusUpdateResponse:
    | ResolversParentTypes['MerchantKYCPartnerAccessUpdateFailureResponse']
    | ResolversParentTypes['MerchantKYCPartnerAccessUpdateSuccessResponse'];
  MerchantKYCPartnerAccessUpdateFailureResponse: MerchantKycPartnerAccessUpdateFailureResponse;
  MerchantKYCPartnerAccessUpdateSuccessResponse: MerchantKycPartnerAccessUpdateSuccessResponse;
  MerchantName: MerchantName;
  MerchantNcEligibilityResponse: MerchantNcEligibilityResponse;
  MerchantNumberField: MerchantNumberField;
  MerchantOnboardingConfig: MerchantOnboardingConfig;
  MerchantOnboardingConfigurationInput: MerchantOnboardingConfigurationInput;
  MerchantOnboardingQuestionDetail: MerchantOnboardingQuestionDetail;
  MerchantOnboardingQuestionDetailInput: MerchantOnboardingQuestionDetailInput;
  MerchantOnboardingQuestionDetailsFailureResponse: MerchantOnboardingQuestionDetailsFailureResponse;
  MerchantOnboardingQuestionDetailsResponse:
    | ResolversParentTypes['MerchantOnboardingQuestionDetailsFailureResponse']
    | ResolversParentTypes['MerchantOnboardingQuestionDetailsSuccessResponse'];
  MerchantOnboardingQuestionDetailsSuccessResponse: MerchantOnboardingQuestionDetailsSuccessResponse;
  MerchantOnboardingQuestionDetailsUpdateResponse: MerchantOnboardingQuestionDetailsUpdateResponse;
  MerchantPaymentAcceptanceChannels: MerchantPaymentAcceptanceChannels;
  MerchantPaymentAcceptanceChannelsInput: MerchantPaymentAcceptanceChannelsInput;
  MerchantPaymentHandle: MerchantPaymentHandle;
  MerchantPaymentHandleAvailabilityFailureResponse: MerchantPaymentHandleAvailabilityFailureResponse;
  MerchantPaymentHandleAvailabilityResponse:
    | ResolversParentTypes['MerchantPaymentHandleAvailabilityFailureResponse']
    | ResolversParentTypes['MerchantPaymentHandleAvailabilitySuccessResponse'];
  MerchantPaymentHandleAvailabilitySuccessResponse: MerchantPaymentHandleAvailabilitySuccessResponse;
  MerchantPaymentHandleCreateFailureResponse: MerchantPaymentHandleCreateFailureResponse;
  MerchantPaymentHandleCreateResponse:
    | ResolversParentTypes['MerchantPaymentHandleCreateFailureResponse']
    | ResolversParentTypes['MerchantPaymentHandleCreateSuccessResponse'];
  MerchantPaymentHandleCreateSuccessResponse: MerchantPaymentHandleCreateSuccessResponse;
  MerchantPaymentHandleEncryptedAmountFailureResponse: MerchantPaymentHandleEncryptedAmountFailureResponse;
  MerchantPaymentHandleEncryptedAmountResponse:
    | ResolversParentTypes['MerchantPaymentHandleEncryptedAmountFailureResponse']
    | ResolversParentTypes['MerchantPaymentHandleEncryptedAmountSuccessResponse'];
  MerchantPaymentHandleEncryptedAmountSuccessResponse: MerchantPaymentHandleEncryptedAmountSuccessResponse;
  MerchantPaymentHandleFailureResponse: MerchantPaymentHandleFailureResponse;
  MerchantPaymentHandleResponse:
    | ResolversParentTypes['MerchantPaymentHandleFailureResponse']
    | ResolversParentTypes['MerchantPaymentHandleSuccessResponse'];
  MerchantPaymentHandleSuccessResponse: MerchantPaymentHandleSuccessResponse;
  MerchantPaymentHandleSuggestionsResponse: MerchantPaymentHandleSuggestionsResponse;
  MerchantPaymentHandleUpdateFailureResponse: MerchantPaymentHandleUpdateFailureResponse;
  MerchantPaymentHandleUpdateResponse:
    | ResolversParentTypes['MerchantPaymentHandleUpdateFailureResponse']
    | ResolversParentTypes['MerchantPaymentHandleUpdateSuccessResponse'];
  MerchantPaymentHandleUpdateSuccessResponse: MerchantPaymentHandleUpdateSuccessResponse;
  MerchantPhoneField: MerchantPhoneField;
  MerchantPhoneInputField: MerchantPhoneInputField;
  MerchantPolicyEmptyPreviewResponse: MerchantPolicyEmptyPreviewResponse;
  MerchantPolicyEmptyResponse: MerchantPolicyEmptyResponse;
  MerchantPolicyEmptyV2PreviewResponse: MerchantPolicyEmptyV2PreviewResponse;
  MerchantPolicyFailureResponse: MerchantPolicyFailureResponse;
  MerchantPolicyPreview: MerchantPolicyPreview;
  MerchantPolicyPreviewFailureResponse: MerchantPolicyPreviewFailureResponse;
  MerchantPolicyPreviewResponse:
    | ResolversParentTypes['MerchantPolicyEmptyPreviewResponse']
    | ResolversParentTypes['MerchantPolicyPreviewFailureResponse']
    | ResolversParentTypes['MerchantPolicyPreviewSuccessResponse'];
  MerchantPolicyPreviewSuccessResponse: MerchantPolicyPreviewSuccessResponse;
  MerchantPolicyPreviewV2FailureResponse: MerchantPolicyPreviewV2FailureResponse;
  MerchantPolicyPreviewV2Response:
    | ResolversParentTypes['MerchantPolicyEmptyV2PreviewResponse']
    | ResolversParentTypes['MerchantPolicyPreviewV2FailureResponse']
    | ResolversParentTypes['MerchantPolicyPreviewV2SuccessResponse'];
  MerchantPolicyPreviewV2SuccessResponse: MerchantPolicyPreviewV2SuccessResponse;
  MerchantPolicyPublishFailureResponse: MerchantPolicyPublishFailureResponse;
  MerchantPolicyPublishResponse:
    | ResolversParentTypes['MerchantPolicyPublishFailureResponse']
    | ResolversParentTypes['MerchantPolicyPublishSuccessResponse'];
  MerchantPolicyPublishSuccessResponse: MerchantPolicyPublishSuccessResponse;
  MerchantPolicyResponse:
    | ResolversParentTypes['MerchantPolicyEmptyResponse']
    | ResolversParentTypes['MerchantPolicyFailureResponse']
    | ResolversParentTypes['MerchantPolicySuccessResponse'];
  MerchantPolicySuccessResponse: MerchantPolicySuccessResponse;
  MerchantPolicyWizardV2EligibilityResponse: MerchantPolicyWizardV2EligibilityResponse;
  MerchantPreference: MerchantPreference;
  MerchantReferralFailureResponse: MerchantReferralFailureResponse;
  MerchantReferralResponse:
    | ResolversParentTypes['MerchantReferralFailureResponse']
    | ResolversParentTypes['MerchantReferralSuccessResponse'];
  MerchantReferralSuccessResponse: MerchantReferralSuccessResponse;
  MerchantSelfServeWorkflow: MerchantSelfServeWorkflow;
  MerchantSelfServeWorkflowStatusFailureResponse: MerchantSelfServeWorkflowStatusFailureResponse;
  MerchantSelfServeWorkflowStatusResponse:
    | ResolversParentTypes['MerchantSelfServeWorkflowStatusFailureResponse']
    | ResolversParentTypes['MerchantSelfServeWorkflowStatusSuccessResponse'];
  MerchantSelfServeWorkflowStatusSuccessResponse: MerchantSelfServeWorkflowStatusSuccessResponse;
  MerchantSettlementConfigFailureResponse: MerchantSettlementConfigFailureResponse;
  MerchantSettlementConfigResponse:
    | ResolversParentTypes['MerchantSettlementConfigFailureResponse']
    | ResolversParentTypes['MerchantSettlementConfigSuccessResponse'];
  MerchantSettlementConfigSuccessResponse: MerchantSettlementConfigSuccessResponse;
  MerchantShopEstablishment: MerchantShopEstablishment;
  MerchantShopEstablishmentInput: MerchantShopEstablishmentInput;
  MerchantSocialMediaURLField: MerchantSocialMediaUrlField;
  MerchantSocialMediaURLInputField: MerchantSocialMediaUrlInputField;
  MerchantStakeholder: MerchantStakeholder;
  MerchantStakeholderInput: MerchantStakeholderInput;
  MerchantStringField: MerchantStringField;
  MerchantStringInputField: MerchantStringInputField;
  MerchantSupportDetails: MerchantSupportDetails;
  MerchantSupportDetailsFailureResponse: MerchantSupportDetailsFailureResponse;
  MerchantSupportDetailsResponse:
    | ResolversParentTypes['MerchantSupportDetailsFailureResponse']
    | ResolversParentTypes['MerchantSupportDetailsSuccessResponse'];
  MerchantSupportDetailsSuccessResponse: MerchantSupportDetailsSuccessResponse;
  MerchantSwitchResponse: MerchantSwitchResponse;
  MerchantTransactionLimit: MerchantTransactionLimit;
  MerchantURLField: MerchantUrlField;
  MerchantURLInputField: MerchantUrlInputField;
  MerchantValidateSocialMediaURLResponse: MerchantValidateSocialMediaUrlResponse;
  MerchantVirtualAccount: MerchantVirtualAccount;
  MerchantVirtualAccountReceiver: MerchantVirtualAccountReceiver;
  MerchantVirtualAccountsResponse: MerchantVirtualAccountsResponse;
  MerchantWebsite: MerchantWebsite;
  MerchantWebsiteAdditionalData: MerchantWebsiteAdditionalData;
  MerchantWebsiteAdditionalDataInput: MerchantWebsiteAdditionalDataInput;
  MerchantWebsiteApplicationDetail: MerchantWebsiteApplicationDetail;
  MerchantWebsiteApplicationInput: MerchantWebsiteApplicationInput;
  MerchantWebsiteDetailsFailureResponse: MerchantWebsiteDetailsFailureResponse;
  MerchantWebsiteDetailsResponse: MerchantWebsiteDetailsResponse;
  MerchantWebsiteDocumentDeleteFailureResponse: MerchantWebsiteDocumentDeleteFailureResponse;
  MerchantWebsiteDocumentDeleteResponse:
    | ResolversParentTypes['MerchantWebsiteDocumentDeleteFailureResponse']
    | ResolversParentTypes['MerchantWebsiteDocumentDeleteSuccessResponse'];
  MerchantWebsiteDocumentDeleteSuccessResponse: MerchantWebsiteDocumentDeleteSuccessResponse;
  MerchantWebsiteDocumentUploadFailureResponse: MerchantWebsiteDocumentUploadFailureResponse;
  MerchantWebsiteDocumentUploadResponse:
    | ResolversParentTypes['MerchantWebsiteDocumentUploadFailureResponse']
    | ResolversParentTypes['MerchantWebsiteDocumentUploadSuccessResponse'];
  MerchantWebsiteDocumentUploadSuccessResponse: MerchantWebsiteDocumentUploadSuccessResponse;
  MerchantWebsitePublishFailureResponse: MerchantWebsitePublishFailureResponse;
  MerchantWebsitePublishResponse:
    | ResolversParentTypes['MerchantWebsitePublishFailureResponse']
    | ResolversParentTypes['MerchantWebsitePublishSuccessResponse'];
  MerchantWebsitePublishSuccessResponse: MerchantWebsitePublishSuccessResponse;
  MerchantWebsiteSection: MerchantWebsiteSection;
  MerchantWebsiteSectionInput: MerchantWebsiteSectionInput;
  MerchantWebsiteTermsAndConditions: MerchantWebsiteTermsAndConditions;
  MerchantWebsitesResponse:
    | ResolversParentTypes['MerchantWebsiteDetailsFailureResponse']
    | ResolversParentTypes['MerchantWebsiteDetailsResponse'];
  MerchantWorkflowClarificationSubmitFailureResponse: MerchantWorkflowClarificationSubmitFailureResponse;
  MerchantWorkflowClarificationSubmitResponse:
    | ResolversParentTypes['MerchantWorkflowClarificationSubmitFailureResponse']
    | ResolversParentTypes['MerchantWorkflowClarificationSubmitSuccessResponse'];
  MerchantWorkflowClarificationSubmitSuccessResponse: MerchantWorkflowClarificationSubmitSuccessResponse;
  Money: Money;
  MoneyInput: MoneyInput;
  Mutation: {};
  MutationResponseInterface:
    | ResolversParentTypes['AadhaarCaptchaVerifyResponse']
    | ResolversParentTypes['AadhaarOtpVerifyResponse']
    | ResolversParentTypes['ApproveIciciPayoutResponse']
    | ResolversParentTypes['ApprovePayoutResponse']
    | ResolversParentTypes['AuthUser']
    | ResolversParentTypes['CouponApplyResponse']
    | ResolversParentTypes['CouponValidateResponse']
    | ResolversParentTypes['DeregisterFCMTokenResponse']
    | ResolversParentTypes['LoginOtpError']
    | ResolversParentTypes['LoginOtpSuccess']
    | ResolversParentTypes['MerchantActivationResponse']
    | ResolversParentTypes['MerchantApiKeyCreateResponse']
    | ResolversParentTypes['MerchantApiKeyRegenerateResponse']
    | ResolversParentTypes['MerchantApiKeysCreateFailure']
    | ResolversParentTypes['MerchantApiKeysCreateSuccess']
    | ResolversParentTypes['MerchantBankAccountDocumentUploadSuccessResponse']
    | ResolversParentTypes['MerchantBankAccountUpdateFailureResponse']
    | ResolversParentTypes['MerchantBankAccountUpdateSuccessResponse']
    | ResolversParentTypes['MerchantBusinessAppDetailsResponse']
    | ResolversParentTypes['MerchantBusinessWebsiteDetailsResponse']
    | ResolversParentTypes['MerchantClarificationDetailsSubmitResponse']
    | ResolversParentTypes['MerchantClarificationDetailsUpdateResponse']
    | ResolversParentTypes['MerchantConfigUpdateResponse']
    | ResolversParentTypes['MerchantConsentFailure']
    | ResolversParentTypes['MerchantContactCreateResponse']
    | ResolversParentTypes['MerchantContactEmailOtpSendFailureResponse']
    | ResolversParentTypes['MerchantContactEmailOtpSendSuccessResponse']
    | ResolversParentTypes['MerchantContactFundAccountCreateResponse']
    | ResolversParentTypes['MerchantContactUpdateResponse']
    | ResolversParentTypes['MerchantDocumentUploadSuccessResponse']
    | ResolversParentTypes['MerchantGstinUpdateAsyncFlowSuccessResponse']
    | ResolversParentTypes['MerchantGstinUpdateInSyncFlowResponse']
    | ResolversParentTypes['MerchantGstinUpdateInSyncWorkFlowCreatedResponse']
    | ResolversParentTypes['MerchantNcEligibilityResponse']
    | ResolversParentTypes['MerchantPaymentHandleCreateSuccessResponse']
    | ResolversParentTypes['MerchantPaymentHandleUpdateSuccessResponse']
    | ResolversParentTypes['MerchantPolicyPublishSuccessResponse']
    | ResolversParentTypes['MerchantPolicyWizardV2EligibilityResponse']
    | ResolversParentTypes['MerchantSwitchResponse']
    | ResolversParentTypes['MerchantWebsiteDocumentDeleteSuccessResponse']
    | ResolversParentTypes['MerchantWebsiteDocumentUploadSuccessResponse']
    | ResolversParentTypes['MerchantWebsitePublishSuccessResponse']
    | ResolversParentTypes['MerchantWorkflowClarificationSubmitSuccessResponse']
    | ResolversParentTypes['NotificationEmailUpdateFailureResponse']
    | ResolversParentTypes['NotificationEmailUpdateSuccessResponse']
    | ResolversParentTypes['NotificationWhatsAppOptIn']
    | ResolversParentTypes['OauthTokenAppleWatchOtp']
    | ResolversParentTypes['OauthTokenAppleWatchResponseError']
    | ResolversParentTypes['OnboardingPaymentOrderCreateFailureResponse']
    | ResolversParentTypes['OnboardingPaymentOrderCreateSuccessResponse']
    | ResolversParentTypes['OnboardingPaymentOrderVerifyResponse']
    | ResolversParentTypes['OrderCreateFailureResponse']
    | ResolversParentTypes['OrderCreateSuccessResponse']
    | ResolversParentTypes['PaymentCaptureResponse']
    | ResolversParentTypes['PaymentLinkCancelResponse']
    | ResolversParentTypes['PaymentLinkCreateResponse']
    | ResolversParentTypes['PaymentLinkNotifyResponse']
    | ResolversParentTypes['PaymentRefundResponse']
    | ResolversParentTypes['PaymentsNewLaunchProductViewUpdate']
    | ResolversParentTypes['PaymentsProductFtuxUpdateResponse']
    | ResolversParentTypes['PayoutCompositeCreateResponse']
    | ResolversParentTypes['PayoutCreateIciciResponse']
    | ResolversParentTypes['PayoutCreateResponse']
    | ResolversParentTypes['PayoutLinkCreateResponse']
    | ResolversParentTypes['PayoutPurposeCreateResponse']
    | ResolversParentTypes['PointOfSalePaymentCreateFailureResponse']
    | ResolversParentTypes['PointOfSalePaymentCreateSuccessResponse']
    | ResolversParentTypes['PointOfSalePaymentUpdateFailureResponse']
    | ResolversParentTypes['PointOfSalePaymentUpdateSuccessResponse']
    | ResolversParentTypes['QRCodeCreateFailureResponse']
    | ResolversParentTypes['QRCodeCreateSuccessResponse']
    | ResolversParentTypes['RegisterBusinessResponse']
    | ResolversParentTypes['RegisterEmailError']
    | ResolversParentTypes['RegisterEmailSuccess']
    | ResolversParentTypes['RegisterFCMTokenResponse']
    | ResolversParentTypes['RegisterMerchantResponseFailure']
    | ResolversParentTypes['RegisterMerchantResponseSuccess']
    | ResolversParentTypes['RegisterMobileVerifyResponseFailure']
    | ResolversParentTypes['RegisterMobileVerifyResponseSuccess']
    | ResolversParentTypes['RejectPayoutResponse']
    | ResolversParentTypes['ResendEmailOtp']
    | ResolversParentTypes['ResendTwoFactorLoginOtpResponse']
    | ResolversParentTypes['SendApprovePayoutBatchOtp']
    | ResolversParentTypes['SendApprovePayoutOtp']
    | ResolversParentTypes['SendCreatePayoutLinkOtp']
    | ResolversParentTypes['SendCreatePayoutOtp']
    | ResolversParentTypes['SendIciciPayoutOtpResponse']
    | ResolversParentTypes['SendPayoutApproveBulkOtp']
    | ResolversParentTypes['SendPayoutCompositeOtp']
    | ResolversParentTypes['SmsNotificationToggle']
    | ResolversParentTypes['TwoFactorAddMobileOtpErrorResponse']
    | ResolversParentTypes['TwoFactorAddMobileOtpSuccessResponse']
    | ResolversParentTypes['TwoFactorAddMobileOtpVerifyErrorResponse']
    | ResolversParentTypes['TwoFactorAddMobileOtpVerifySuccessResponse']
    | ResolversParentTypes['TwoFactorEmailOtpVerifyFailureResponse']
    | ResolversParentTypes['TwoFactorEmailOtpVerifySuccessResponse']
    | ResolversParentTypes['TwoFactorOtpFailureResponse']
    | ResolversParentTypes['TwoFactorOtpSuccessResponse']
    | ResolversParentTypes['TwoFactorPasswordCreateErrorResponse']
    | ResolversParentTypes['TwoFactorPasswordCreateSuccessResponse']
    | ResolversParentTypes['TwoFactorUnverifiedMobileVerifyResponse']
    | ResolversParentTypes['UpdateMerchantConsentResponse']
    | ResolversParentTypes['UserContactDetailsUpdateResponse']
    | ResolversParentTypes['UserDeviceAnalyticsResponse']
    | ResolversParentTypes['UserOtpVerifyResponse']
    | ResolversParentTypes['VendorPaymentCancelResponse']
    | ResolversParentTypes['VendorPaymentPayoutCreateResponse']
    | ResolversParentTypes['WhatsappNotificationToggle']
    | ResolversParentTypes['merchantConfigurationUpdateResponse'];
  NonNegativeInt: Scalars['NonNegativeInt'];
  NotificationEmailUpdateFailureResponse: NotificationEmailUpdateFailureResponse;
  NotificationEmailUpdateResponse:
    | ResolversParentTypes['NotificationEmailUpdateFailureResponse']
    | ResolversParentTypes['NotificationEmailUpdateSuccessResponse'];
  NotificationEmailUpdateSuccessResponse: NotificationEmailUpdateSuccessResponse;
  NotificationWhatsAppOptIn: NotificationWhatsAppOptIn;
  OauthTokenAppleWatchOtp: OauthTokenAppleWatchOtp;
  OauthTokenAppleWatchResponse:
    | ResolversParentTypes['OauthTokenAppleWatchResponseError']
    | ResolversParentTypes['OauthTokenAppleWatchResponseSuccess'];
  OauthTokenAppleWatchResponseError: OauthTokenAppleWatchResponseError;
  OauthTokenAppleWatchResponseSuccess: OauthTokenAppleWatchResponseSuccess;
  OnboardingPaymentOrderCreateFailureResponse: OnboardingPaymentOrderCreateFailureResponse;
  OnboardingPaymentOrderCreateResponse:
    | ResolversParentTypes['OnboardingPaymentOrderCreateFailureResponse']
    | ResolversParentTypes['OnboardingPaymentOrderCreateSuccessResponse'];
  OnboardingPaymentOrderCreateSuccessResponse: OnboardingPaymentOrderCreateSuccessResponse;
  OnboardingPaymentOrderVerifyResponse: OnboardingPaymentOrderVerifyResponse;
  OnboardingWidget: OnboardingWidget;
  Order: Order;
  OrderAmount: OrderAmount;
  OrderCreateFailureResponse: OrderCreateFailureResponse;
  OrderCreateResponse:
    | ResolversParentTypes['OrderCreateFailureResponse']
    | ResolversParentTypes['OrderCreateSuccessResponse'];
  OrderCreateSuccessResponse: OrderCreateSuccessResponse;
  OrderDate: OrderDate;
  Organisation: Organisation;
  OrganisationEmail: OrganisationEmail;
  OrganisationLogo: OrganisationLogo;
  OrganisationName: OrganisationName;
  OverViewResponseType: OverViewResponseType;
  PPTrackingSettings: PpTrackingSettings;
  PageAcquirerData: PageAcquirerData;
  PageItem: PageItem;
  PageItemTaxDetails: PageItemTaxDetails;
  PaginationResponseInterface:
    | ResolversParentTypes['InvoicesResponse']
    | ResolversParentTypes['MerchantContactFundAccountsResponse']
    | ResolversParentTypes['MerchantContactsResponse']
    | ResolversParentTypes['MerchantVirtualAccountsResponse']
    | ResolversParentTypes['PaymentLinksResponse']
    | ResolversParentTypes['PaymentPageTransactionResponse']
    | ResolversParentTypes['PaymentPagesResponse']
    | ResolversParentTypes['PaymentsResponse']
    | ResolversParentTypes['PayoutBatchesResponse']
    | ResolversParentTypes['PayoutLinksResponse']
    | ResolversParentTypes['PayoutsResponse']
    | ResolversParentTypes['QRCodesResponse']
    | ResolversParentTypes['RefundsResponse']
    | ResolversParentTypes['SettlementsResponse']
    | ResolversParentTypes['TransactionsResponse']
    | ResolversParentTypes['VendorPaymentsResponse'];
  PartnerConfigFailure: PartnerConfigFailure;
  PartnerConfigResponse:
    | ResolversParentTypes['PartnerConfigFailure']
    | ResolversParentTypes['PartnerConfigSuccess'];
  PartnerConfigSuccess: PartnerConfigSuccess;
  PartnerWebhookSettings: PartnerWebhookSettings;
  Payment: Omit<Payment, 'method'> & { method?: Maybe<ResolversParentTypes['PaymentMethod']> };
  PaymentAggregationSummary: PaymentAggregationSummary;
  PaymentAmount: PaymentAmount;
  PaymentAnalytics: PaymentAnalytics;
  PaymentAnalyticsFilterBy: PaymentAnalyticsFilterBy;
  PaymentAnalyticsResponse: PaymentAnalyticsResponse;
  PaymentAnalyticsWidget: PaymentAnalyticsWidget;
  PaymentCaptureResponse: PaymentCaptureResponse;
  PaymentDetails: PaymentDetails;
  PaymentEmiDetails: PaymentEmiDetails;
  PaymentError: PaymentError;
  PaymentHandleWidget: PaymentHandleWidget;
  PaymentInstantRefundEligibilityAmount: PaymentInstantRefundEligibilityAmount;
  PaymentInstantRefundEligibilityResponse: PaymentInstantRefundEligibilityResponse;
  PaymentLink: PaymentLink;
  PaymentLinkAmount: PaymentLinkAmount;
  PaymentLinkCancelResponse: PaymentLinkCancelResponse;
  PaymentLinkCreateResponse: PaymentLinkCreateResponse;
  PaymentLinkDate: PaymentLinkDate;
  PaymentLinkNotifyBy: PaymentLinkNotifyBy;
  PaymentLinkNotifyByInput: PaymentLinkNotifyByInput;
  PaymentLinkNotifyResponse: PaymentLinkNotifyResponse;
  PaymentLinkReminder: PaymentLinkReminder;
  PaymentLinksResponse: PaymentLinksResponse;
  PaymentMethod:
    | ResolversParentTypes['PaymentMethodApp']
    | ResolversParentTypes['PaymentMethodBankTransfer']
    | ResolversParentTypes['PaymentMethodCard']
    | ResolversParentTypes['PaymentMethodCardlessEmi']
    | ResolversParentTypes['PaymentMethodEmandate']
    | ResolversParentTypes['PaymentMethodEmi']
    | ResolversParentTypes['PaymentMethodNetBanking']
    | ResolversParentTypes['PaymentMethodPayLater']
    | ResolversParentTypes['PaymentMethodUPITransfer']
    | ResolversParentTypes['PaymentMethodWallet'];
  PaymentMethodApp: PaymentMethodApp;
  PaymentMethodBankTransfer: PaymentMethodBankTransfer;
  PaymentMethodCard: PaymentMethodCard;
  PaymentMethodCardExpiry: PaymentMethodCardExpiry;
  PaymentMethodCardlessEmi: PaymentMethodCardlessEmi;
  PaymentMethodEmandate: PaymentMethodEmandate;
  PaymentMethodEmi: PaymentMethodEmi;
  PaymentMethodNetBanking: PaymentMethodNetBanking;
  PaymentMethodPayLater: PaymentMethodPayLater;
  PaymentMethodUPITransfer: PaymentMethodUpiTransfer;
  PaymentMethodWallet: PaymentMethodWallet;
  PaymentOverviewResponse: PaymentOverviewResponse;
  PaymentPage: PaymentPage;
  PaymentPageAmount: PaymentPageAmount;
  PaymentPageDate: PaymentPageDate;
  PaymentPageItem: PaymentPageItem;
  PaymentPageSettings: PaymentPageSettings;
  PaymentPageSupportDetails: PaymentPageSupportDetails;
  PaymentPageTransaction: PaymentPageTransaction;
  PaymentPageTransactionResponse: PaymentPageTransactionResponse;
  PaymentPagesResponse: PaymentPagesResponse;
  PaymentPayerBankAccount: PaymentPayerBankAccount;
  PaymentRefund: PaymentRefund;
  PaymentRefundResponse: PaymentRefundResponse;
  PaymentRefundSpeed: PaymentRefundSpeed;
  PaymentSummaryResponse: PaymentSummaryResponse;
  PaymentTerm: PaymentTerm;
  PaymentVirtualAccount: PaymentVirtualAccount;
  PaymentVirtualAccountAmount: PaymentVirtualAccountAmount;
  PaymentVirtualAccountDates: PaymentVirtualAccountDates;
  PaymentsNewLaunchProductViewUpdate: PaymentsNewLaunchProductViewUpdate;
  PaymentsProductFtuxUpdateResponse: PaymentsProductFtuxUpdateResponse;
  PaymentsResponse: PaymentsResponse;
  PaymentsWidgetError: PaymentsWidgetError;
  PaymentsWidgets: Omit<PaymentsWidgets, 'widgets'> & {
    widgets: Array<ResolversParentTypes['Widget']>;
  };
  Payout: Omit<Payout, 'workflow'> & { workflow?: Maybe<ResolversParentTypes['PayoutWorkflow']> };
  PayoutApproveBulkResponse:
    | ResolversParentTypes['PayoutApproveBulkResponseFailure']
    | ResolversParentTypes['PayoutApproveBulkResponseSuccess'];
  PayoutApproveBulkResponseFailure: PayoutApproveBulkResponseFailure;
  PayoutApproveBulkResponseSuccess: PayoutApproveBulkResponseSuccess;
  PayoutBatch: PayoutBatch;
  PayoutBatchDates: PayoutBatchDates;
  PayoutBatchPayoutsCount: PayoutBatchPayoutsCount;
  PayoutBatchesResponse: PayoutBatchesResponse;
  PayoutCompositeCreateResponse: PayoutCompositeCreateResponse;
  PayoutCompositeMerchantContactInput: PayoutCompositeMerchantContactInput;
  PayoutCreateIciciResponse: PayoutCreateIciciResponse;
  PayoutCreateResponse: PayoutCreateResponse;
  PayoutDate: PayoutDate;
  PayoutFee: PayoutFee;
  PayoutLink: PayoutLink;
  PayoutLinkCreateResponse: PayoutLinkCreateResponse;
  PayoutLinkDate: PayoutLinkDate;
  PayoutLinkSendVia: PayoutLinkSendVia;
  PayoutLinkSentVia: PayoutLinkSentVia;
  PayoutLinksResponse: PayoutLinksResponse;
  PayoutPendingOnInput: PayoutPendingOnInput;
  PayoutPurpose: PayoutPurpose;
  PayoutPurposeCreateResponse: PayoutPurposeCreateResponse;
  PayoutRejectBulkResponse:
    | ResolversParentTypes['PayoutRejectBulkResponseFailure']
    | ResolversParentTypes['PayoutRejectBulkResponseSuccess'];
  PayoutRejectBulkResponseFailure: PayoutRejectBulkResponseFailure;
  PayoutRejectBulkResponseSuccess: PayoutRejectBulkResponseSuccess;
  PayoutSource: PayoutSource;
  PayoutWorkflow: ResolversParentTypes['PayoutWorkflowHistory'] | ResolversParentTypes['Workflow'];
  PayoutWorkflowHistory: PayoutWorkflowHistory;
  PayoutWorkflowRole: PayoutWorkflowRole;
  PayoutWorkflowRoleChecker: PayoutWorkflowRoleChecker;
  PayoutWorkflowStep: PayoutWorkflowStep;
  PayoutsPendingSummary: PayoutsPendingSummary;
  PayoutsQueuedSummary:
    | ResolversParentTypes['PayoutsQueuedSummaryBeneficiaryBankDown']
    | ResolversParentTypes['PayoutsQueuedSummaryLowBalance']
    | ResolversParentTypes['PayoutsQueuedSummaryNEFTLimitExhausted']
    | ResolversParentTypes['PayoutsQueuedSummaryNEFTWindowClosed']
    | ResolversParentTypes['PayoutsQueuedSummaryNPCISystemDown']
    | ResolversParentTypes['PayoutsQueuedSummaryWithoutReason'];
  PayoutsQueuedSummaryBeneficiaryBankDown: PayoutsQueuedSummaryBeneficiaryBankDown;
  PayoutsQueuedSummaryLowBalance: PayoutsQueuedSummaryLowBalance;
  PayoutsQueuedSummaryNEFTLimitExhausted: PayoutsQueuedSummaryNeftLimitExhausted;
  PayoutsQueuedSummaryNEFTWindowClosed: PayoutsQueuedSummaryNeftWindowClosed;
  PayoutsQueuedSummaryNPCISystemDown: PayoutsQueuedSummaryNpciSystemDown;
  PayoutsQueuedSummaryWithoutReason: PayoutsQueuedSummaryWithoutReason;
  PayoutsResponse: PayoutsResponse;
  PayoutsScheduledSummary:
    | ResolversParentTypes['PayoutsScheduledSummaryAllTime']
    | ResolversParentTypes['PayoutsScheduledSummaryNextMonth']
    | ResolversParentTypes['PayoutsScheduledSummaryNextTwoDays']
    | ResolversParentTypes['PayoutsScheduledSummaryNextWeek']
    | ResolversParentTypes['PayoutsScheduledSummaryToday'];
  PayoutsScheduledSummaryAllTime: PayoutsScheduledSummaryAllTime;
  PayoutsScheduledSummaryNextMonth: PayoutsScheduledSummaryNextMonth;
  PayoutsScheduledSummaryNextTwoDays: PayoutsScheduledSummaryNextTwoDays;
  PayoutsScheduledSummaryNextWeek: PayoutsScheduledSummaryNextWeek;
  PayoutsScheduledSummaryToday: PayoutsScheduledSummaryToday;
  PayoutsSummary: Omit<PayoutsSummary, 'queued' | 'scheduled'> & {
    queued: Array<ResolversParentTypes['PayoutsQueuedSummary']>;
    scheduled: Array<ResolversParentTypes['PayoutsScheduledSummary']>;
  };
  Phone: Phone;
  PhoneInput: PhoneInput;
  PointOfSale: PointOfSale;
  PointOfSaleKeyFetchResponse: PointOfSaleKeyFetchResponse;
  PointOfSalePaymentCreateFailureResponse: PointOfSalePaymentCreateFailureResponse;
  PointOfSalePaymentCreateResponse:
    | ResolversParentTypes['PointOfSalePaymentCreateFailureResponse']
    | ResolversParentTypes['PointOfSalePaymentCreateSuccessResponse'];
  PointOfSalePaymentCreateSuccessResponse: PointOfSalePaymentCreateSuccessResponse;
  PointOfSalePaymentTransactionInput: PointOfSalePaymentTransactionInput;
  PointOfSalePaymentUpdateFailureResponse: PointOfSalePaymentUpdateFailureResponse;
  PointOfSalePaymentUpdateResponse:
    | ResolversParentTypes['PointOfSalePaymentUpdateFailureResponse']
    | ResolversParentTypes['PointOfSalePaymentUpdateSuccessResponse'];
  PointOfSalePaymentUpdateSuccessResponse: PointOfSalePaymentUpdateSuccessResponse;
  PositiveInt: Scalars['PositiveInt'];
  QRCode: QrCode;
  QRCodeCreateFailureResponse: QrCodeCreateFailureResponse;
  QRCodeCreateResponse:
    | ResolversParentTypes['QRCodeCreateFailureResponse']
    | ResolversParentTypes['QRCodeCreateSuccessResponse'];
  QRCodeCreateSuccessResponse: QrCodeCreateSuccessResponse;
  QRCodeDate: QrCodeDate;
  QRCodePaymentDetail: QrCodePaymentDetail;
  QRCodesResponse: QrCodesResponse;
  Query: {};
  RecentTransactionsWidget: RecentTransactionsWidget;
  RefreshAccessToken: RefreshAccessToken;
  RefundsResponse: RefundsResponse;
  RegisterBusinessResponse: RegisterBusinessResponse;
  RegisterEmail:
    | ResolversParentTypes['RegisterEmailError']
    | ResolversParentTypes['RegisterEmailSuccess'];
  RegisterEmailError: RegisterEmailError;
  RegisterEmailSuccess: RegisterEmailSuccess;
  RegisterEmailVerifyResponse:
    | ResolversParentTypes['RegisterEmailVerifyResponseFailure']
    | ResolversParentTypes['RegisterEmailVerifyResponseSuccess'];
  RegisterEmailVerifyResponseFailure: RegisterEmailVerifyResponseFailure;
  RegisterEmailVerifyResponseSuccess: RegisterEmailVerifyResponseSuccess;
  RegisterFCMTokenResponse: RegisterFcmTokenResponse;
  RegisterMerchantResponse:
    | ResolversParentTypes['RegisterMerchantResponseFailure']
    | ResolversParentTypes['RegisterMerchantResponseSuccess'];
  RegisterMerchantResponseFailure: RegisterMerchantResponseFailure;
  RegisterMerchantResponseSuccess: RegisterMerchantResponseSuccess;
  RegisterMobileVerifyResponse:
    | ResolversParentTypes['RegisterMobileVerifyResponseFailure']
    | ResolversParentTypes['RegisterMobileVerifyResponseSuccess'];
  RegisterMobileVerifyResponseFailure: RegisterMobileVerifyResponseFailure;
  RegisterMobileVerifyResponseSuccess: RegisterMobileVerifyResponseSuccess;
  RegisterOAuth:
    | ResolversParentTypes['AuthUser']
    | ResolversParentTypes['RegisterOAuthEmailError']
    | ResolversParentTypes['RegisterOAuthEmailExist']
    | ResolversParentTypes['RegisterOAuthInvalidTokenError'];
  RegisterOAuthEmailError: RegisterOAuthEmailError;
  RegisterOAuthEmailExist: RegisterOAuthEmailExist;
  RegisterOAuthInvalidTokenError: RegisterOAuthInvalidTokenError;
  RejectPayoutBatchResponse:
    | ResolversParentTypes['RejectPayoutBatchResponseFailure']
    | ResolversParentTypes['RejectPayoutBatchResponseSuccess'];
  RejectPayoutBatchResponseFailure: RejectPayoutBatchResponseFailure;
  RejectPayoutBatchResponseSuccess: RejectPayoutBatchResponseSuccess;
  RejectPayoutResponse: RejectPayoutResponse;
  ResendEmailOtp: ResendEmailOtp;
  ResendTwoFactorLoginOtpResponse: ResendTwoFactorLoginOtpResponse;
  ResetPasswordEmail: ResetPasswordEmail;
  SendApprovePayoutBatchOtp: SendApprovePayoutBatchOtp;
  SendApprovePayoutOtp: SendApprovePayoutOtp;
  SendCreatePayoutLinkOtp: SendCreatePayoutLinkOtp;
  SendCreatePayoutOtp: SendCreatePayoutOtp;
  SendIciciPayoutOtpResponse: SendIciciPayoutOtpResponse;
  SendPayoutApproveBulkOtp: SendPayoutApproveBulkOtp;
  SendPayoutCompositeOtp: SendPayoutCompositeOtp;
  Settlement: Settlement;
  SettlementAmount: SettlementAmount;
  SettlementBreakup: SettlementBreakup;
  SettlementCycle: SettlementCycle;
  SettlementsResponse: SettlementsResponse;
  SettlementsWidget: SettlementsWidget;
  SmsNotificationStatusResponse: SmsNotificationStatusResponse;
  SmsNotificationToggle: SmsNotificationToggle;
  String: Scalars['String'];
  TDSCategory: TdsCategory;
  Transaction: Omit<Transaction, 'source'> & { source: ResolversParentTypes['TransactionSource'] };
  TransactionAmount: TransactionAmount;
  TransactionError: TransactionError;
  TransactionSource:
    | ResolversParentTypes['Payout']
    | ResolversParentTypes['TransactionSourceAdjustment']
    | ResolversParentTypes['TransactionSourceBankTransfer']
    | ResolversParentTypes['TransactionSourceExternal']
    | ResolversParentTypes['TransactionSourceFundAccountValidation']
    | ResolversParentTypes['TransactionSourceReversal'];
  TransactionSourceAdjustment: TransactionSourceAdjustment;
  TransactionSourceBankTransfer: TransactionSourceBankTransfer;
  TransactionSourceBankTransferPayee: TransactionSourceBankTransferPayee;
  TransactionSourceBankTransferPayer: TransactionSourceBankTransferPayer;
  TransactionSourceDetails: TransactionSourceDetails;
  TransactionSourceExternal: TransactionSourceExternal;
  TransactionSourceFundAccountValidation: TransactionSourceFundAccountValidation;
  TransactionSourceReversal: TransactionSourceReversal;
  TransactionStatus: TransactionStatus;
  TransactionsResponse: TransactionsResponse;
  TwoFactorAddMobileOtpErrorResponse: TwoFactorAddMobileOtpErrorResponse;
  TwoFactorAddMobileOtpResponse:
    | ResolversParentTypes['TwoFactorAddMobileOtpErrorResponse']
    | ResolversParentTypes['TwoFactorAddMobileOtpSuccessResponse'];
  TwoFactorAddMobileOtpSuccessResponse: TwoFactorAddMobileOtpSuccessResponse;
  TwoFactorAddMobileOtpVerifyErrorResponse: TwoFactorAddMobileOtpVerifyErrorResponse;
  TwoFactorAddMobileOtpVerifyResponse:
    | ResolversParentTypes['TwoFactorAddMobileOtpVerifyErrorResponse']
    | ResolversParentTypes['TwoFactorAddMobileOtpVerifySuccessResponse'];
  TwoFactorAddMobileOtpVerifySuccessResponse: TwoFactorAddMobileOtpVerifySuccessResponse;
  TwoFactorAuthUpdateFailureResponse: TwoFactorAuthUpdateFailureResponse;
  TwoFactorAuthUpdateResponse:
    | ResolversParentTypes['TwoFactorAuthUpdateFailureResponse']
    | ResolversParentTypes['TwoFactorAuthUpdateSuccessResponse'];
  TwoFactorAuthUpdateSuccessResponse: TwoFactorAuthUpdateSuccessResponse;
  TwoFactorEmailOtpVerifyFailureResponse: TwoFactorEmailOtpVerifyFailureResponse;
  TwoFactorEmailOtpVerifyResponse:
    | ResolversParentTypes['TwoFactorEmailOtpVerifyFailureResponse']
    | ResolversParentTypes['TwoFactorEmailOtpVerifySuccessResponse'];
  TwoFactorEmailOtpVerifySuccessResponse: TwoFactorEmailOtpVerifySuccessResponse;
  TwoFactorOtpFailureResponse: TwoFactorOtpFailureResponse;
  TwoFactorOtpResponse:
    | ResolversParentTypes['TwoFactorOtpFailureResponse']
    | ResolversParentTypes['TwoFactorOtpSuccessResponse'];
  TwoFactorOtpSuccessResponse: TwoFactorOtpSuccessResponse;
  TwoFactorPasswordCreateErrorResponse: TwoFactorPasswordCreateErrorResponse;
  TwoFactorPasswordCreateResponse:
    | ResolversParentTypes['TwoFactorPasswordCreateErrorResponse']
    | ResolversParentTypes['TwoFactorPasswordCreateSuccessResponse'];
  TwoFactorPasswordCreateSuccessResponse: TwoFactorPasswordCreateSuccessResponse;
  TwoFactorPasswordEnabledErrorResponse: TwoFactorPasswordEnabledErrorResponse;
  TwoFactorPasswordEnabledResponse:
    | ResolversParentTypes['TwoFactorPasswordEnabledErrorResponse']
    | ResolversParentTypes['TwoFactorPasswordEnabledSuccessResponse'];
  TwoFactorPasswordEnabledSuccessResponse: TwoFactorPasswordEnabledSuccessResponse;
  TwoFactorUnverifiedMobileVerifyResponse: TwoFactorUnverifiedMobileVerifyResponse;
  URL: Scalars['URL'];
  UpdateMerchantConsentResponse: UpdateMerchantConsentResponse;
  Upload: Scalars['Upload'];
  User: User;
  UserAuthentication: UserAuthentication;
  UserContactDetails: UserContactDetails;
  UserContactDetailsUpdateResponse: UserContactDetailsUpdateResponse;
  UserDeviceAnalyticsResponse: UserDeviceAnalyticsResponse;
  UserLogout: UserLogout;
  UserOtpVerifyResponse: UserOtpVerifyResponse;
  UserRole: UserRole;
  VPA: Scalars['VPA'];
  ValidateVpaFailureResponse: ValidateVpaFailureResponse;
  ValidateVpaResponse:
    | ResolversParentTypes['ValidateVpaFailureResponse']
    | ResolversParentTypes['ValidateVpaSuccessResponse'];
  ValidateVpaSuccessResponse: ValidateVpaSuccessResponse;
  VendorPayment: VendorPayment;
  VendorPaymentCancelResponse: VendorPaymentCancelResponse;
  VendorPaymentDates: VendorPaymentDates;
  VendorPaymentGST: VendorPaymentGst;
  VendorPaymentInvoice: VendorPaymentInvoice;
  VendorPaymentInvoiceAttachment: VendorPaymentInvoiceAttachment;
  VendorPaymentPayoutAmounts: VendorPaymentPayoutAmounts;
  VendorPaymentPayoutCreateResponse: VendorPaymentPayoutCreateResponse;
  VendorPaymentTDS: VendorPaymentTds;
  VendorPaymentsResponse: VendorPaymentsResponse;
  WhatsappNotificationStatusResponse: WhatsappNotificationStatusResponse;
  WhatsappNotificationToggle: WhatsappNotificationToggle;
  Widget:
    | ResolversParentTypes['AcceptPaymentsWidget']
    | ResolversParentTypes['OnboardingWidget']
    | ResolversParentTypes['PaymentAnalyticsWidget']
    | ResolversParentTypes['PaymentHandleWidget']
    | ResolversParentTypes['PaymentsWidgetError']
    | ResolversParentTypes['RecentTransactionsWidget']
    | ResolversParentTypes['SettlementsWidget'];
  Workflow: Workflow;
  WorkflowConfig: WorkflowConfig;
  WorkflowConfigState: Omit<WorkflowConfigState, 'rule'> & {
    rule: ResolversParentTypes['WorkflowConfigStateRulePayout'];
  };
  WorkflowConfigStateRulePayout:
    | ResolversParentTypes['WorkflowConfigStateRulePayoutTypeBetween']
    | ResolversParentTypes['WorkflowConfigStateRulePayoutTypeChecker']
    | ResolversParentTypes['WorkflowConfigStateRulePayoutTypeMergeStates'];
  WorkflowConfigStateRulePayoutTypeBetween: WorkflowConfigStateRulePayoutTypeBetween;
  WorkflowConfigStateRulePayoutTypeChecker: WorkflowConfigStateRulePayoutTypeChecker;
  WorkflowConfigStateRulePayoutTypeMergeStates: WorkflowConfigStateRulePayoutTypeMergeStates;
  WorkflowConfigStateTransition: WorkflowConfigStateTransition;
  WorkflowConfigTemplate: WorkflowConfigTemplate;
  WorkflowCreator: WorkflowCreator;
  WorkflowState: Omit<WorkflowState, 'rule'> & {
    rule: ResolversParentTypes['WorkflowConfigStateRulePayout'];
  };
  WorkflowStateAction: WorkflowStateAction;
  WorkflowStateActionActor: WorkflowStateActionActor;
  WorkflowStateDates: WorkflowStateDates;
  merchantConfigurationUpdateResponse: MerchantConfigurationUpdateResponse;
  userOtpResponse: UserOtpResponse;
};

export type AadhaarCaptchaResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarCaptchaResponse'] = ResolversParentTypes['AadhaarCaptchaResponse'],
> = {
  captcha?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  isSessionExpired?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarCaptchaV2FailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarCaptchaV2FailureResponse'] = ResolversParentTypes['AadhaarCaptchaV2FailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarCaptchaV2ResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarCaptchaV2Response'] = ResolversParentTypes['AadhaarCaptchaV2Response'],
> = {
  __resolveType: TypeResolveFn<
    'AadhaarCaptchaV2FailureResponse' | 'AadhaarCaptchaV2SuccessResponse',
    ParentType,
    ContextType
  >;
};

export type AadhaarCaptchaV2SuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarCaptchaV2SuccessResponse'] = ResolversParentTypes['AadhaarCaptchaV2SuccessResponse'],
> = {
  captcha?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isSessionExpired?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarCaptchaVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarCaptchaVerifyResponse'] = ResolversParentTypes['AadhaarCaptchaVerifyResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<
    Maybe<ResolversTypes['AadhaarCaptchaVerifyErrorTypeEnum']>,
    ParentType,
    ContextType
  >;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarDigilockerOtpFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerOtpFailureResponse'] = ResolversParentTypes['AadhaarDigilockerOtpFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<ResolversTypes['AadhaarOtpDigilockerErrorEnum'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarDigilockerOtpResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerOtpResponse'] = ResolversParentTypes['AadhaarDigilockerOtpResponse'],
> = {
  __resolveType: TypeResolveFn<
    'AadhaarDigilockerOtpFailureResponse' | 'AadhaarDigilockerOtpSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type AadhaarDigilockerOtpSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerOtpSuccessResponse'] = ResolversParentTypes['AadhaarDigilockerOtpSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  requestId?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarDigilockerOtpVerifyFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerOtpVerifyFailureResponse'] = ResolversParentTypes['AadhaarDigilockerOtpVerifyFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<ResolversTypes['AadhaarOtpDigilockerErrorEnum'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarDigilockerOtpVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerOtpVerifyResponse'] = ResolversParentTypes['AadhaarDigilockerOtpVerifyResponse'],
> = {
  __resolveType: TypeResolveFn<
    'AadhaarDigilockerOtpVerifyFailureResponse' | 'AadhaarDigilockerOtpVerifySuccessResponse',
    ParentType,
    ContextType
  >;
};

export type AadhaarDigilockerOtpVerifySuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerOtpVerifySuccessResponse'] = ResolversParentTypes['AadhaarDigilockerOtpVerifySuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarDigilockerRedirectionUrlFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerRedirectionUrlFailureResponse'] = ResolversParentTypes['AadhaarDigilockerRedirectionUrlFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<
    ResolversTypes['AadhaarDigilockerRedirectionUrlErrorTypeEnum'],
    ParentType,
    ContextType
  >;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarDigilockerRedirectionUrlResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerRedirectionUrlResponse'] = ResolversParentTypes['AadhaarDigilockerRedirectionUrlResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'AadhaarDigilockerRedirectionUrlFailureResponse'
    | 'AadhaarDigilockerRedirectionUrlSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type AadhaarDigilockerRedirectionUrlSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerRedirectionUrlSuccessResponse'] = ResolversParentTypes['AadhaarDigilockerRedirectionUrlSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  redirectionUrl?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarDigilockerRedirectionUrlVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerRedirectionUrlVerifyResponse'] = ResolversParentTypes['AadhaarDigilockerRedirectionUrlVerifyResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'AadhaarDigilockerRedirectionVerificationFailureResponse'
    | 'AadhaarDigilockerRedirectionVerificationSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type AadhaarDigilockerRedirectionVerificationFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerRedirectionVerificationFailureResponse'] = ResolversParentTypes['AadhaarDigilockerRedirectionVerificationFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<
    ResolversTypes['AadhaarDigilockerRedirectionVerificationErrorTypeEnum'],
    ParentType,
    ContextType
  >;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarDigilockerRedirectionVerificationSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarDigilockerRedirectionVerificationSuccessResponse'] = ResolversParentTypes['AadhaarDigilockerRedirectionVerificationSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isValid?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AadhaarOtpVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AadhaarOtpVerifyResponse'] = ResolversParentTypes['AadhaarOtpVerifyResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<
    Maybe<ResolversTypes['AadhaarOtpVerifyErrorTypeEnum']>,
    ParentType,
    ContextType
  >;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AcceptPaymentsProductResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AcceptPaymentsProduct'] = ResolversParentTypes['AcceptPaymentsProduct'],
> = {
  description?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  isFtuxComplete?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isNewLaunch?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  title?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AcceptPaymentsWidgetResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AcceptPaymentsWidget'] = ResolversParentTypes['AcceptPaymentsWidget'],
> = {
  products?: Resolver<
    Maybe<Array<ResolversTypes['AcceptPaymentsProduct']>>,
    ParentType,
    ContextType
  >;
  title?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['PaymentsWidgetTypeEnum'], ParentType, ContextType>;
  variant?: Resolver<ResolversTypes['WidgetVariantEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AccountVerificationOtpResendResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AccountVerificationOtpResendResponse'] = ResolversParentTypes['AccountVerificationOtpResendResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AccountVerificationOtpResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AccountVerificationOtpResponse'] = ResolversParentTypes['AccountVerificationOtpResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AcquirerDataResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AcquirerData'] = ResolversParentTypes['AcquirerData'],
> = {
  arn?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  rrn?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  utr?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AddressResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Address'] = ResolversParentTypes['Address'],
> = {
  city?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  country?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  isPrimary?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  line1?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  line2?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  state?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  zipcode?: Resolver<Maybe<ResolversTypes['PositiveInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AddressByPincodeFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AddressByPincodeFailureResponse'] = ResolversParentTypes['AddressByPincodeFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AddressByPincodeResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AddressByPincodeResponse'] = ResolversParentTypes['AddressByPincodeResponse'],
> = {
  __resolveType: TypeResolveFn<
    'AddressByPincodeFailureResponse' | 'AddressByPincodeSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type AddressByPincodeSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AddressByPincodeSuccessResponse'] = ResolversParentTypes['AddressByPincodeSuccessResponse'],
> = {
  city?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  state?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  stateCode?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AggregationResultTypeResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AggregationResultType'] = ResolversParentTypes['AggregationResultType'],
> = {
  status?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  value?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ApproveIciciPayoutResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ApproveIciciPayoutResponse'] = ResolversParentTypes['ApproveIciciPayoutResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ApprovePayoutBatchResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ApprovePayoutBatchResponse'] = ResolversParentTypes['ApprovePayoutBatchResponse'],
> = {
  __resolveType: TypeResolveFn<
    'ApprovePayoutBatchResponseFailure' | 'ApprovePayoutBatchResponseSuccess',
    ParentType,
    ContextType
  >;
};

export type ApprovePayoutBatchResponseFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ApprovePayoutBatchResponseFailure'] = ResolversParentTypes['ApprovePayoutBatchResponseFailure'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  failedPayoutBatchIds?: Resolver<Maybe<Array<ResolversTypes['ID']>>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ApprovePayoutBatchResponseSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ApprovePayoutBatchResponseSuccess'] = ResolversParentTypes['ApprovePayoutBatchResponseSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ApprovePayoutResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ApprovePayoutResponse'] = ResolversParentTypes['ApprovePayoutResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AuthResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Auth'] = ResolversParentTypes['Auth'],
> = {
  __resolveType: TypeResolveFn<
    'AuthUnauthenticated' | 'AuthUnregistered' | 'AuthUser',
    ParentType,
    ContextType
  >;
};

export type AuthUnauthenticatedResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AuthUnauthenticated'] = ResolversParentTypes['AuthUnauthenticated'],
> = {
  errorCode?: Resolver<Maybe<ResolversTypes['AuthErrorCodeEnum']>, ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AuthUnregisteredResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AuthUnregistered'] = ResolversParentTypes['AuthUnregistered'],
> = {
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type AuthUserResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['AuthUser'] = ResolversParentTypes['AuthUser'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  source?: Resolver<Maybe<ResolversTypes['AuthSourceEnum']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  user?: Resolver<ResolversTypes['User'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type BankResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Bank'] = ResolversParentTypes['Bank'],
> = {
  address?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  branchName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  centre?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  city?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  code?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  contactNumber?: Resolver<ResolversTypes['Phone'], ParentType, ContextType>;
  district?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  ifsc?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  isImpsEnabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isNeftEnabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isRtgsEnabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isUpiEnabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  micrCode?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  state?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  swiftCode?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export interface BigIntScalarConfig extends GraphQLScalarTypeConfig<ResolversTypes['BigInt'], any> {
  name: 'BigInt';
}

export type BusinessTypeResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['BusinessType'] = ResolversParentTypes['BusinessType'],
> = {
  label?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  status?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  value?: Resolver<ResolversTypes['MerchantBusinessTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type CheckoutOptionsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['CheckoutOptions'] = ResolversParentTypes['CheckoutOptions'],
> = {
  email?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  phone?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ClarificationCommentResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ClarificationComment'] = ResolversParentTypes['ClarificationComment'],
> = {
  text?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['MerchantCommentTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ClarificationCommentsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ClarificationComments'] = ResolversParentTypes['ClarificationComments'],
> = {
  comment?: Resolver<Maybe<ResolversTypes['ClarificationComment']>, ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  fieldDetails?: Resolver<
    Maybe<ResolversTypes['MerchantClarificationFieldValues']>,
    ParentType,
    ContextType
  >;
  messageFrom?: Resolver<ResolversTypes['MerchantClarificationFromEnum'], ParentType, ContextType>;
  ncCount?: Resolver<ResolversTypes['Int'], ParentType, ContextType>;
  status?: Resolver<ResolversTypes['MerchantClarificationStatusEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ConfigDataResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ConfigData'] = ResolversParentTypes['ConfigData'],
> = {
  bankAccountVerificationAttemptCount?: Resolver<
    Maybe<ResolversTypes['Int']>,
    ParentType,
    ContextType
  >;
  companyPanVerificationAttemptCount?: Resolver<
    Maybe<ResolversTypes['Int']>,
    ParentType,
    ContextType
  >;
  couponPopupCount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  isMtuCouponAvailable?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isMtuCouponCongratulatoryPopupEnabled?: Resolver<
    ResolversTypes['Boolean'],
    ParentType,
    ContextType
  >;
  isSignedUpReferee?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  promoterPanVerificationAttemptCount?: Resolver<
    Maybe<ResolversTypes['Int']>,
    ParentType,
    ContextType
  >;
  refereeNames?: Resolver<Maybe<Array<Maybe<ResolversTypes['String']>>>, ParentType, ContextType>;
  referralAmount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  referralSuccessPopupCount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  referredCount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  showFtuxFinalScreen?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  showFtuxFirstPaymentBanner?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  upiTerminalProcurementStatus?: Resolver<
    Maybe<ResolversTypes['UpiTerminalProcurementStatusEnum']>,
    ParentType,
    ContextType
  >;
  websiteIncompleteSoftNudgeCount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type CouponApplyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['CouponApplyResponse'] = ResolversParentTypes['CouponApplyResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type CouponValidateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['CouponValidateResponse'] = ResolversParentTypes['CouponValidateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  credits?: Resolver<Maybe<ResolversTypes['BigInt']>, ParentType, ContextType>;
  expiryDays?: Resolver<Maybe<ResolversTypes['PositiveInt']>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type CurrencyResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Currency'] = ResolversParentTypes['Currency'],
> = {
  code?: Resolver<ResolversTypes['CurrencyCodeEnum'], ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['CurrencyNameEnum']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type CustomerResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Customer'] = ResolversParentTypes['Customer'],
> = {
  address?: Resolver<Maybe<ResolversTypes['CustomerAddress']>, ParentType, ContextType>;
  email?: Resolver<Maybe<ResolversTypes['EmailAddress']>, ParentType, ContextType>;
  id?: Resolver<Maybe<ResolversTypes['ID']>, ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  phone?: Resolver<Maybe<ResolversTypes['Phone']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type CustomerAddressResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['CustomerAddress'] = ResolversParentTypes['CustomerAddress'],
> = {
  billing?: Resolver<Maybe<ResolversTypes['Address']>, ParentType, ContextType>;
  shipping?: Resolver<Maybe<ResolversTypes['Address']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export interface DateTimeScalarConfig
  extends GraphQLScalarTypeConfig<ResolversTypes['DateTime'], any> {
  name: 'DateTime';
}

export type DeregisterFcmTokenResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['DeregisterFCMTokenResponse'] = ResolversParentTypes['DeregisterFCMTokenResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export interface EmailAddressScalarConfig
  extends GraphQLScalarTypeConfig<ResolversTypes['EmailAddress'], any> {
  name: 'EmailAddress';
}

export type FailedPaymentsOverviewFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['FailedPaymentsOverviewFailureResponse'] = ResolversParentTypes['FailedPaymentsOverviewFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type FailedPaymentsOverviewResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['FailedPaymentsOverviewResponse'] = ResolversParentTypes['FailedPaymentsOverviewResponse'],
> = {
  __resolveType: TypeResolveFn<
    'FailedPaymentsOverviewFailureResponse' | 'FailedPaymentsOverviewSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type FailedPaymentsOverviewSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['FailedPaymentsOverviewSuccessResponse'] = ResolversParentTypes['FailedPaymentsOverviewSuccessResponse'],
> = {
  bank?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['OverViewResponseType']>>>,
    ParentType,
    ContextType
  >;
  business?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['OverViewResponseType']>>>,
    ParentType,
    ContextType
  >;
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  customer?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['OverViewResponseType']>>>,
    ParentType,
    ContextType
  >;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  others?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['OverViewResponseType']>>>,
    ParentType,
    ContextType
  >;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type GoalTrackerMetaDataResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['GoalTrackerMetaData'] = ResolversParentTypes['GoalTrackerMetaData'],
> = {
  availableUnits?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  collectedAmount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  displayAvailableUnits?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  displayDaysLeft?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  displaySoldUnits?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  displaySupporterCount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  goalAmount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  goalEndTimestamp?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  goalEndTimestampFormatted?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  soldUnits?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  supporterCount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type GoalTrackerSettingsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['GoalTrackerSettings'] = ResolversParentTypes['GoalTrackerSettings'],
> = {
  isActive?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  metaData?: Resolver<Maybe<ResolversTypes['GoalTrackerMetaData']>, ParentType, ContextType>;
  trackerType?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ImageResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Image'] = ResolversParentTypes['Image'],
> = {
  alt?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  src?: Resolver<ResolversTypes['URL'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type InvoiceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Invoice'] = ResolversParentTypes['Invoice'],
> = {
  amount?: Resolver<ResolversTypes['InvoiceAmount'], ParentType, ContextType>;
  comments?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  customer?: Resolver<Maybe<ResolversTypes['Customer']>, ParentType, ContextType>;
  dates?: Resolver<Maybe<ResolversTypes['InvoiceDate']>, ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  isPartiallyPayable?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  items?: Resolver<Array<ResolversTypes['InvoiceItem']>, ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  number?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  payment?: Resolver<Maybe<ResolversTypes['Payment']>, ParentType, ContextType>;
  receiptNumber?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  smsStatus?: Resolver<Maybe<ResolversTypes['InvoiceSmsStatusEnum']>, ParentType, ContextType>;
  status?: Resolver<Maybe<ResolversTypes['InvoiceStatusEnum']>, ParentType, ContextType>;
  type?: Resolver<Maybe<ResolversTypes['InvoiceTypeEnum']>, ParentType, ContextType>;
  url?: Resolver<Maybe<ResolversTypes['URL']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type InvoiceAmountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['InvoiceAmount'] = ResolversParentTypes['InvoiceAmount'],
> = {
  due?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  generated?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  paid?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type InvoiceDateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['InvoiceDate'] = ResolversParentTypes['InvoiceDate'],
> = {
  cancelledAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  createdAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  expireBy?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  expiredAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  issuedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  paidAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type InvoiceItemResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['InvoiceItem'] = ResolversParentTypes['InvoiceItem'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  number?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  quantity?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type InvoicesResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['InvoicesResponse'] = ResolversParentTypes['InvoicesResponse'],
> = {
  hasMore?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  invoices?: Resolver<Array<ResolversTypes['Invoice']>, ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export interface JsonScalarConfig extends GraphQLScalarTypeConfig<ResolversTypes['JSON'], any> {
  name: 'JSON';
}

export interface JsonObjectScalarConfig
  extends GraphQLScalarTypeConfig<ResolversTypes['JSONObject'], any> {
  name: 'JSONObject';
}

export type LoginOtpErrorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['LoginOtpError'] = ResolversParentTypes['LoginOtpError'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<Maybe<ResolversTypes['LoginOtpErrorCodeEnum']>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type LoginOtpResendErrorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['LoginOtpResendError'] = ResolversParentTypes['LoginOtpResendError'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<ResolversTypes['LoginOtpErrorCodeEnum'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type LoginOtpResendResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['LoginOtpResendResponse'] = ResolversParentTypes['LoginOtpResendResponse'],
> = {
  __resolveType: TypeResolveFn<
    'LoginOtpResendError' | 'LoginOtpResendSuccess',
    ParentType,
    ContextType
  >;
};

export type LoginOtpResendSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['LoginOtpResendSuccess'] = ResolversParentTypes['LoginOtpResendSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type LoginOtpResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['LoginOtpResponse'] = ResolversParentTypes['LoginOtpResponse'],
> = {
  __resolveType: TypeResolveFn<'LoginOtpError' | 'LoginOtpSuccess', ParentType, ContextType>;
};

export type LoginOtpSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['LoginOtpSuccess'] = ResolversParentTypes['LoginOtpSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Merchant'] = ResolversParentTypes['Merchant'],
> = {
  activation?: Resolver<ResolversTypes['MerchantActivation'], ParentType, ContextType>;
  apiKeys?: Resolver<Array<ResolversTypes['MerchantApiKey']>, ParentType, ContextType>;
  bank?: Resolver<ResolversTypes['MerchantBank'], ParentType, ContextType>;
  bankingAccounts?: Resolver<
    Maybe<Array<ResolversTypes['MerchantBankingAccount']>>,
    ParentType,
    ContextType
  >;
  business?: Resolver<ResolversTypes['MerchantBusiness'], ParentType, ContextType>;
  configurations?: Resolver<
    Maybe<ResolversTypes['MerchantConfiguration']>,
    ParentType,
    ContextType
  >;
  contactPerson?: Resolver<ResolversTypes['MerchantContactPerson'], ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  document?: Resolver<ResolversTypes['MerchantDocument'], ParentType, ContextType>;
  domesticLimit?: Resolver<Maybe<ResolversTypes['BigInt']>, ParentType, ContextType>;
  hasApiKeyAccess?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  internationalLimit?: Resolver<Maybe<ResolversTypes['BigInt']>, ParentType, ContextType>;
  isFundsOnHold?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isPresignupComplete?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  isTransactionCouponApplied?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  isTwoFactorEnabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  logo?: Resolver<Maybe<ResolversTypes['URL']>, ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['MerchantName']>, ParentType, ContextType>;
  role?: Resolver<Maybe<ResolversTypes['MerchantRoleEnum']>, ParentType, ContextType>;
  stakeholder?: Resolver<ResolversTypes['MerchantStakeholder'], ParentType, ContextType>;
  users?: Resolver<Array<ResolversTypes['User']>, ParentType, ContextType>;
  websiteTermsAndConditions?: Resolver<
    Maybe<ResolversTypes['MerchantWebsiteTermsAndConditions']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantAcceptanceChannelResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantAcceptanceChannel'] = ResolversParentTypes['MerchantAcceptanceChannel'],
> = {
  accept?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  complianceConsent?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  socialMediaUrls?: Resolver<
    Array<ResolversTypes['MerchantSocialMediaURLField']>,
    ParentType,
    ContextType
  >;
  urls?: Resolver<Array<ResolversTypes['MerchantURLField']>, ParentType, ContextType>;
  value?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantAcceptanceChannelWhatsappSmsEmailResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantAcceptanceChannelWhatsappSmsEmail'] = ResolversParentTypes['MerchantAcceptanceChannelWhatsappSmsEmail'],
> = {
  accept?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantActivationResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantActivation'] = ResolversParentTypes['MerchantActivation'],
> = {
  activationStatusChangeLogs?: Resolver<
    Array<ResolversTypes['MerchantActivationStatusEnum']>,
    ParentType,
    ContextType
  >;
  allowedStatus?: Resolver<
    Array<Maybe<ResolversTypes['MerchantActivationStatusEnum']>>,
    ParentType,
    ContextType
  >;
  canSubmitForm?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  dedupe?: Resolver<Maybe<ResolversTypes['MerchantActivationDedupe']>, ParentType, ContextType>;
  documentsSubmittedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  feeBasedGating?: Resolver<
    Maybe<ResolversTypes['MerchantFeeBasedGating']>,
    ParentType,
    ContextType
  >;
  flow?: Resolver<Maybe<ResolversTypes['MerchantActivationFlow']>, ParentType, ContextType>;
  isActivated?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isAutoKycDone?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isDedupe?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isFormLocked?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  isFormSubmitted?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isHardLimitReached?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isInternational?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isTransacted?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  merchantEscalations?: Resolver<ResolversTypes['MerchantEscalations'], ParentType, ContextType>;
  milestone?: Resolver<
    Maybe<ResolversTypes['MerchantActivationMilestoneEnum']>,
    ParentType,
    ContextType
  >;
  paymentsActivatedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  progressPercent?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  status?: Resolver<Maybe<ResolversTypes['MerchantActivationStatusEnum']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantActivationDedupeResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantActivationDedupe'] = ResolversParentTypes['MerchantActivationDedupe'],
> = {
  isMatch?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isUnderReview?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantActivationEscalationsBreachedResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantActivationEscalationsBreached'] = ResolversParentTypes['MerchantActivationEscalationsBreached'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  currentEscalationLimit?: Resolver<
    ResolversTypes['MerchantEscalationLimit'],
    ParentType,
    ContextType
  >;
  escalationAction?: Resolver<
    Maybe<ResolversTypes['MerchantEscalationAction']>,
    ParentType,
    ContextType
  >;
  escalationType?: Resolver<ResolversTypes['MerchantEscalationTypeEnum'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  nextEscalationLimit?: Resolver<
    Maybe<ResolversTypes['MerchantEscalationLimit']>,
    ParentType,
    ContextType
  >;
  transactionLimit?: Resolver<ResolversTypes['MerchantTransactionLimit'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantActivationEscalationsNotBreachedResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantActivationEscalationsNotBreached'] = ResolversParentTypes['MerchantActivationEscalationsNotBreached'],
> = {
  transactionLimit?: Resolver<ResolversTypes['MerchantTransactionLimit'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantActivationFlowResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantActivationFlow'] = ResolversParentTypes['MerchantActivationFlow'],
> = {
  domestic?: Resolver<Maybe<ResolversTypes['MerchantActivationFlowEnum']>, ParentType, ContextType>;
  international?: Resolver<
    Maybe<ResolversTypes['MerchantActivationFlowEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantActivationResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantActivationResponse'] = ResolversParentTypes['MerchantActivationResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  merchant?: Resolver<ResolversTypes['Merchant'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantAddressResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantAddress'] = ResolversParentTypes['MerchantAddress'],
> = {
  city?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  country?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  district?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  line1?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  line2?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  state?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  zipCode?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantAnalyticsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantAnalytics'] = ResolversParentTypes['MerchantAnalytics'],
> = {
  data?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantApiKeyResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantApiKey'] = ResolversParentTypes['MerchantApiKey'],
> = {
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  expiredAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  updatedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantApiKeyCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantApiKeyCreateResponse'] = ResolversParentTypes['MerchantApiKeyCreateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  expiredAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  secret?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  updatedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantApiKeyInterfaceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantApiKeyInterface'] = ResolversParentTypes['MerchantApiKeyInterface'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantApiKey'
    | 'MerchantApiKeyCreateResponse'
    | 'MerchantApiKeyRegenerateNew'
    | 'MerchantApiKeyRegenerateOld'
    | 'MerchantApiKeysCreateSuccess',
    ParentType,
    ContextType
  >;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  expiredAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  updatedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
};

export type MerchantApiKeyRegenerateNewResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantApiKeyRegenerateNew'] = ResolversParentTypes['MerchantApiKeyRegenerateNew'],
> = {
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  expiredAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  secret?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  updatedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantApiKeyRegenerateOldResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantApiKeyRegenerateOld'] = ResolversParentTypes['MerchantApiKeyRegenerateOld'],
> = {
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  expiredAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  updatedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantApiKeyRegenerateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantApiKeyRegenerateResponse'] = ResolversParentTypes['MerchantApiKeyRegenerateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  newApiKey?: Resolver<ResolversTypes['MerchantApiKeyRegenerateNew'], ParentType, ContextType>;
  oldApiKey?: Resolver<ResolversTypes['MerchantApiKeyRegenerateOld'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantApiKeysCreateFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantApiKeysCreateFailure'] = ResolversParentTypes['MerchantApiKeysCreateFailure'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantApiKeysCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantApiKeysCreateResponse'] = ResolversParentTypes['MerchantApiKeysCreateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantApiKeysCreateFailure' | 'MerchantApiKeysCreateSuccess',
    ParentType,
    ContextType
  >;
};

export type MerchantApiKeysCreateSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantApiKeysCreateSuccess'] = ResolversParentTypes['MerchantApiKeysCreateSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  expiredAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  secret?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  updatedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantAverageOrderFieldResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantAverageOrderField'] = ResolversParentTypes['MerchantAverageOrderField'],
> = {
  clarificationReasons?: Resolver<
    Array<ResolversTypes['MerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  value?: Resolver<
    Maybe<ResolversTypes['MerchantAverageOrderFieldValue']>,
    ParentType,
    ContextType
  >;
  verificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantAverageOrderFieldValueResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantAverageOrderFieldValue'] = ResolversParentTypes['MerchantAverageOrderFieldValue'],
> = {
  max?: Resolver<ResolversTypes['Int'], ParentType, ContextType>;
  min?: Resolver<ResolversTypes['Int'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBalanceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBalance'] = ResolversParentTypes['MerchantBalance'],
> = {
  accountType?: Resolver<
    Maybe<ResolversTypes['MerchantBalanceAccountTypeEnum']>,
    ParentType,
    ContextType
  >;
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  productType?: Resolver<ResolversTypes['MerchantBalanceProductTypeEnum'], ParentType, ContextType>;
  virtualAccountsResponse?: Resolver<
    Maybe<ResolversTypes['MerchantVirtualAccountsResponse']>,
    ParentType,
    ContextType,
    RequireFields<
      MerchantBalanceVirtualAccountsResponseArgs,
      'virtualAccountsLimit' | 'virtualAccountsOffset'
    >
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBank'] = ResolversParentTypes['MerchantBank'],
> = {
  accountName?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  accountNumber?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  fuzzyScore?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  ifsc?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  verificationErrorCode?: Resolver<
    Maybe<ResolversTypes['MerchantBankVerificationErrorCodeEnum']>,
    ParentType,
    ContextType
  >;
  verificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankAccountDetailsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankAccountDetails'] = ResolversParentTypes['MerchantBankAccountDetails'],
> = {
  accountNumber?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  beneficiaryName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  ifscCode?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankAccountDocumentUploadFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankAccountDocumentUploadFailureResponse'] = ResolversParentTypes['MerchantBankAccountDocumentUploadFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankAccountDocumentUploadResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankAccountDocumentUploadResponse'] = ResolversParentTypes['MerchantBankAccountDocumentUploadResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantBankAccountDocumentUploadFailureResponse'
    | 'MerchantBankAccountDocumentUploadSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantBankAccountDocumentUploadSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankAccountDocumentUploadSuccessResponse'] = ResolversParentTypes['MerchantBankAccountDocumentUploadSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankAccountUpdateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankAccountUpdateFailureResponse'] = ResolversParentTypes['MerchantBankAccountUpdateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankAccountUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankAccountUpdateResponse'] = ResolversParentTypes['MerchantBankAccountUpdateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantBankAccountUpdateFailureResponse' | 'MerchantBankAccountUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantBankAccountUpdateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankAccountUpdateSuccessResponse'] = ResolversParentTypes['MerchantBankAccountUpdateSuccessResponse'],
> = {
  bankAccountDetails?: Resolver<
    Maybe<ResolversTypes['MerchantBankAccountDetails']>,
    ParentType,
    ContextType
  >;
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isSyncFlow?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isTimeOut?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isWorkFlowCreated?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankDetailsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankDetails'] = ResolversParentTypes['MerchantBankDetails'],
> = {
  accountName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  accountNumber?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  bankAccountId?: Resolver<Maybe<ResolversTypes['ID']>, ParentType, ContextType>;
  bankName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  ifsc?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  updatedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankDetailsFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankDetailsFailureResponse'] = ResolversParentTypes['MerchantBankDetailsFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankDetailsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankDetailsResponse'] = ResolversParentTypes['MerchantBankDetailsResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantBankDetailsFailureResponse' | 'MerchantBankDetailsSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantBankDetailsSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankDetailsSuccessResponse'] = ResolversParentTypes['MerchantBankDetailsSuccessResponse'],
> = {
  bank?: Resolver<ResolversTypes['MerchantBankDetails'], ParentType, ContextType>;
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankingAccountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankingAccount'] = ResolversParentTypes['MerchantBankingAccount'],
> = {
  balance?: Resolver<
    Maybe<ResolversTypes['MerchantBankingAccountBalance']>,
    ParentType,
    ContextType
  >;
  currency?: Resolver<ResolversTypes['Currency'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  ifsc?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  number?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  partnerBankName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  pincode?: Resolver<Maybe<ResolversTypes['PositiveInt']>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['MerchantBankingAccountStatusEnum'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['MerchantBankingAccountTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankingAccountBalanceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankingAccountBalance'] = ResolversParentTypes['MerchantBankingAccountBalance'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  lastCheckedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankingAccountsBalanceResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankingAccountsBalanceResponse'] = ResolversParentTypes['MerchantBankingAccountsBalanceResponse'],
> = {
  balances?: Resolver<
    Array<ResolversTypes['MerchantBankingAccountBalance']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBankingRoleResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBankingRole'] = ResolversParentTypes['MerchantBankingRole'],
> = {
  id?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBusinessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBusiness'] = ResolversParentTypes['MerchantBusiness'],
> = {
  address?: Resolver<ResolversTypes['MerchantBusinessAddress'], ParentType, ContextType>;
  averageOrder?: Resolver<ResolversTypes['MerchantAverageOrderField'], ParentType, ContextType>;
  billingLabel?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  blacklistedConsentCategory?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessPan?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  category?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  companyCin?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  gstin?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  model?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  parentCategory?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  paymentAcceptanceChannels?: Resolver<
    ResolversTypes['MerchantPaymentAcceptanceChannels'],
    ParentType,
    ContextType
  >;
  shopEstablishment?: Resolver<
    ResolversTypes['MerchantShopEstablishment'],
    ParentType,
    ContextType
  >;
  subCategory?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['MerchantBusinessTypeField'], ParentType, ContextType>;
  websites?: Resolver<Array<ResolversTypes['MerchantURLField']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBusinessAddressResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBusinessAddress'] = ResolversParentTypes['MerchantBusinessAddress'],
> = {
  operation?: Resolver<ResolversTypes['MerchantAddress'], ParentType, ContextType>;
  registered?: Resolver<ResolversTypes['MerchantAddress'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBusinessAppDetailsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBusinessAppDetailsResponse'] = ResolversParentTypes['MerchantBusinessAppDetailsResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBusinessCategoriesResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBusinessCategoriesResponse'] = ResolversParentTypes['MerchantBusinessCategoriesResponse'],
> = {
  categoryName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  categoryValue?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  subCategories?: Resolver<
    Array<ResolversTypes['MerchantBusinessSubCategory']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBusinessParentCategoryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBusinessParentCategory'] = ResolversParentTypes['MerchantBusinessParentCategory'],
> = {
  categories?: Resolver<
    Array<ResolversTypes['MerchantBusinessCategoriesResponse']>,
    ParentType,
    ContextType
  >;
  parentCategoryName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  parentCategoryValue?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBusinessSubCategoryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBusinessSubCategory'] = ResolversParentTypes['MerchantBusinessSubCategory'],
> = {
  activationFlow?: Resolver<ResolversTypes['MerchantActivationFlowEnum'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  nonRegisteredActivationFlow?: Resolver<
    ResolversTypes['MerchantActivationFlowEnum'],
    ParentType,
    ContextType
  >;
  tags?: Resolver<Array<Maybe<ResolversTypes['String']>>, ParentType, ContextType>;
  value?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBusinessTypeFieldResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBusinessTypeField'] = ResolversParentTypes['MerchantBusinessTypeField'],
> = {
  clarificationReasons?: Resolver<
    Array<ResolversTypes['MerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  value?: Resolver<Maybe<ResolversTypes['MerchantBusinessTypeEnum']>, ParentType, ContextType>;
  verificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBusinessTypesResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBusinessTypesResponse'] = ResolversParentTypes['MerchantBusinessTypesResponse'],
> = {
  registered?: Resolver<Array<ResolversTypes['BusinessType']>, ParentType, ContextType>;
  unregistered?: Resolver<Array<ResolversTypes['BusinessType']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantBusinessWebsiteDetailsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantBusinessWebsiteDetailsResponse'] = ResolversParentTypes['MerchantBusinessWebsiteDetailsResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantClarificationDetailResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantClarificationDetail'] = ResolversParentTypes['MerchantClarificationDetail'],
> = {
  aadharBack?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  aadharFront?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  address?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  affiliationCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  amfiCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  appstoreUrl?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  ayushCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  bankDetails?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  bankStatement?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  bankVerificationLetter?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  barCouncilCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  bbpsDocument?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  bisCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  boardResolutionLetter?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  brandTieUpDocument?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  businessCategory?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  businessCorrespondentDocument?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  businessDba?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  businessDescription?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  businessPan?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  businessPanName?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  businessPanNumber?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  businessRegistration?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  businessType?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  businessWebsite?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  cancelledCheque?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  cancelledChequeVideo?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  cinDetails?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  contactEmail?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  contactMobile?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  contactName?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  copywriteLicense?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  cpvReport?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  dealershipRightsCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  dgcaCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  domainOwnershipDocument?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  dotCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  driverLicenseBack?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  driverLicenseFront?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  epfSchemeCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  fdaCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  fdaLicense?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  ffmcLicense?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  form8a?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  form10ac?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  form12a?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  form80g?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  form2020b2121b?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  fssaiCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  fssaiLicense?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  giaCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  giiCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  govtAuthorisationLetter?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  gstCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  gstDetails?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  iataCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  iatoLicense?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  iecLicense?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  invoice?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  irctcAgentAgreement?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  irdaCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  irdaiRegistrationCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  legalOpinionDocument?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  liquorLicense?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  manufacturingLicense?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  merchantServiceAgreement?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  mmtcPampLicense?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  msmeCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  msoDocument?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  nationalHousingBankCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  nbfcRegistrationCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  ncCount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  operationAddress?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  passportBack?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  passportFront?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  pciDssCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  personalPan?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  pescoLicense?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  pesoLicense?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  pharmacyDrugLicense?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  playstoreUrl?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  pmWaniCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  ppiLicense?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  promoterPanDetails?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  proofOfProfession?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  rbiCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  registeredAddress?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  reraLicense?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  resellerAgreement?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  safegoldPartnershipDocument?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  sebiRegistrationCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  shopEstablishmentCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  shopEstablishmentNumber?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  slaAmfiCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  slaDealershipAgreement?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  slaDocument?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  slaFfmcLicense?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  slaIataCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  slaIrdaiRegistrationCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  slaNbfcRegistrationCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  slaSebiRegistrationCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  tradeLicense?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  traiCertificate?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  undertaking?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  undertakingDocument?: Resolver<
    Maybe<ResolversTypes['MerchantClarifications']>,
    ParentType,
    ContextType
  >;
  voterIdBack?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  voterIdFront?: Resolver<Maybe<ResolversTypes['MerchantClarifications']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantClarificationDetailsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantClarificationDetailsResponse'] = ResolversParentTypes['MerchantClarificationDetailsResponse'],
> = {
  clarificationDetails?: Resolver<
    ResolversTypes['MerchantClarificationDetail'],
    ParentType,
    ContextType
  >;
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantClarificationDetailsSubmitResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantClarificationDetailsSubmitResponse'] = ResolversParentTypes['MerchantClarificationDetailsSubmitResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantClarificationDetailsUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantClarificationDetailsUpdateResponse'] = ResolversParentTypes['MerchantClarificationDetailsUpdateResponse'],
> = {
  clarificationDetails?: Resolver<
    ResolversTypes['MerchantClarificationDetail'],
    ParentType,
    ContextType
  >;
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantClarificationFieldValuesResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantClarificationFieldValues'] = ResolversParentTypes['MerchantClarificationFieldValues'],
> = {
  aadharBack?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  aadharFront?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  affiliationCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  amfiCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  appstoreUrl?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  ayushCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  bankAccountName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  bankAccountNumber?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  bankBranchIfsc?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  bankStatement?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  bankVerificationLetter?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  barCouncilCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  bbpsDocument?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  bisCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  boardResolutionLetter?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  brandTieUpDocument?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessCorrespondentDocument?: Resolver<
    Maybe<ResolversTypes['String']>,
    ParentType,
    ContextType
  >;
  businessDba?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessDescription?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessModel?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessOperationAddress?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessOperationCity?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessOperationPin?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessOperationState?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessPan?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessRegisteredAddress?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessRegisteredCity?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessRegisteredPin?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessRegisteredState?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessRegistration?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessType?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  businessWebsite?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  cancelledCheque?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  cancelledChequeVideo?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  companyCin?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  companyPan?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  contactEmail?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  contactMobile?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  contactName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  copywriteLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  cpvReport?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  dealershipRightsCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  dgcaCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  domainOwnershipDocument?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  dotCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  driverLicenseBack?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  driverLicenseFront?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  epfSchemeCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  fdaCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  fdaLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  ffmcLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  form8a?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  form10ac?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  form12a?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  form80g?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  form2020b2121b?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  fssaiCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  fssaiLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  giaCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  giiCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  govtAuthorisationLetter?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  gstCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  gstin?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  iataCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  iatoLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  iecLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  invoice?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  irctcAgentAgreement?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  irdaCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  irdaiRegistrationCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  legalOpinionDocument?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  liquorLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  manufacturingLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  merchantServiceAgreement?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  mmtcPampLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  msmeCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  msoDocument?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  nationalHousingBankCertificate?: Resolver<
    Maybe<ResolversTypes['String']>,
    ParentType,
    ContextType
  >;
  nbfcRegistrationCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  passportBack?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  passportFront?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  pciDssCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  personalPan?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  pescoLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  pesoLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  pharmacyDrugLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  playstoreUrl?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  pmWaniCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  ppiLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  promoterPan?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  promoterPanName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  proofOfProfession?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  rbiCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  reraLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  resellerAgreement?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  safegoldPartnershipDocument?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  sebiRegistrationCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  shopEstablishmentCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  shopEstablishmentNumber?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  slaAmfiCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  slaDealershipAgreement?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  slaDocument?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  slaFfmcLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  slaIataCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  slaIrdaiRegistrationCertificate?: Resolver<
    Maybe<ResolversTypes['String']>,
    ParentType,
    ContextType
  >;
  slaNbfcRegistrationCertificate?: Resolver<
    Maybe<ResolversTypes['String']>,
    ParentType,
    ContextType
  >;
  slaSebiRegistrationCertificate?: Resolver<
    Maybe<ResolversTypes['String']>,
    ParentType,
    ContextType
  >;
  tradeLicense?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  traiCertificate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  undertaking?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  undertakingDocument?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  voterIdBack?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  voterIdFront?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantClarificationsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantClarifications'] = ResolversParentTypes['MerchantClarifications'],
> = {
  comments?: Resolver<Array<ResolversTypes['ClarificationComments']>, ParentType, ContextType>;
  fieldValues?: Resolver<
    ResolversTypes['MerchantClarificationFieldValues'],
    ParentType,
    ContextType
  >;
  fields?: Resolver<Array<ResolversTypes['String']>, ParentType, ContextType>;
  ncCount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['MerchantClarificationStatusEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantConfigResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantConfig'] = ResolversParentTypes['MerchantConfig'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantConfigFailure' | 'MerchantOnboardingConfig',
    ParentType,
    ContextType
  >;
};

export type MerchantConfigFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantConfigFailure'] = ResolversParentTypes['MerchantConfigFailure'],
> = {
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantConfigUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantConfigUpdateResponse'] = ResolversParentTypes['MerchantConfigUpdateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantConfigurationResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantConfiguration'] = ResolversParentTypes['MerchantConfiguration'],
> = {
  defaultRefundSpeed?: Resolver<
    Maybe<ResolversTypes['PaymentRefundSpeedRequestedEnum']>,
    ParentType,
    ContextType
  >;
  transactionReportEmail?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['String']>>>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantConsentFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantConsentFailure'] = ResolversParentTypes['MerchantConsentFailure'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantConsentResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantConsentResponse'] = ResolversParentTypes['MerchantConsentResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantConsentFailure' | 'MerchantConsentSuccess',
    ParentType,
    ContextType
  >;
};

export type MerchantConsentSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantConsentSuccess'] = ResolversParentTypes['MerchantConsentSuccess'],
> = {
  partnerAccess?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantConsentsFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantConsentsFailureResponse'] = ResolversParentTypes['MerchantConsentsFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<ResolversTypes['MerchantConsentsErrorTypeEnum'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantConsentsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantConsentsResponse'] = ResolversParentTypes['MerchantConsentsResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantConsentsFailureResponse' | 'MerchantConsentsSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantConsentsSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantConsentsSuccessResponse'] = ResolversParentTypes['MerchantConsentsSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContact'] = ResolversParentTypes['MerchantContact'],
> = {
  active?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  email?: Resolver<Maybe<ResolversTypes['EmailAddress']>, ParentType, ContextType>;
  fundAccounts?: Resolver<
    Maybe<ResolversTypes['MerchantContactFundAccountsResponse']>,
    ParentType,
    ContextType,
    Partial<MerchantContactFundAccountsArgs>
  >;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  paymentTerm?: Resolver<Maybe<ResolversTypes['PaymentTerm']>, ParentType, ContextType>;
  phone?: Resolver<Maybe<ResolversTypes['Phone']>, ParentType, ContextType>;
  referenceId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  tdsCategory?: Resolver<Maybe<ResolversTypes['TDSCategory']>, ParentType, ContextType>;
  type?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactCreateResponse'] = ResolversParentTypes['MerchantContactCreateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  merchantContact?: Resolver<Maybe<ResolversTypes['MerchantContact']>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactEmailOtpSendFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactEmailOtpSendFailureResponse'] = ResolversParentTypes['MerchantContactEmailOtpSendFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactEmailOtpSendResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactEmailOtpSendResponse'] = ResolversParentTypes['MerchantContactEmailOtpSendResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantContactEmailOtpSendFailureResponse' | 'MerchantContactEmailOtpSendSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantContactEmailOtpSendSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactEmailOtpSendSuccessResponse'] = ResolversParentTypes['MerchantContactEmailOtpSendSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  otpVerificationToken?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactFundAccountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactFundAccount'] = ResolversParentTypes['MerchantContactFundAccount'],
> = {
  active?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  batchId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  contact?: Resolver<ResolversTypes['MerchantContact'], ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  details?: Resolver<
    Maybe<ResolversTypes['MerchantContactFundAccountDetails']>,
    ParentType,
    ContextType
  >;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['MerchantContactFundAccountTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactFundAccountCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactFundAccountCreateResponse'] = ResolversParentTypes['MerchantContactFundAccountCreateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  fundAccount?: Resolver<ResolversTypes['MerchantContactFundAccount'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactFundAccountDetailsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactFundAccountDetails'] = ResolversParentTypes['MerchantContactFundAccountDetails'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantContactFundAccountDetailsBankAccount'
    | 'MerchantContactFundAccountDetailsCard'
    | 'MerchantContactFundAccountDetailsVPA'
    | 'MerchantContactFundAccountDetailsWallet',
    ParentType,
    ContextType
  >;
};

export type MerchantContactFundAccountDetailsBankAccountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactFundAccountDetailsBankAccount'] = ResolversParentTypes['MerchantContactFundAccountDetailsBankAccount'],
> = {
  bankName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  holderName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  ifsc?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  number?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactFundAccountDetailsCardResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactFundAccountDetailsCard'] = ResolversParentTypes['MerchantContactFundAccountDetailsCard'],
> = {
  issuerName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  last4Digits?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  network?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactFundAccountDetailsVpaResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactFundAccountDetailsVPA'] = ResolversParentTypes['MerchantContactFundAccountDetailsVPA'],
> = {
  address?: Resolver<ResolversTypes['VPA'], ParentType, ContextType>;
  handle?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactFundAccountDetailsWalletResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactFundAccountDetailsWallet'] = ResolversParentTypes['MerchantContactFundAccountDetailsWallet'],
> = {
  email?: Resolver<Maybe<ResolversTypes['EmailAddress']>, ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  phone?: Resolver<ResolversTypes['Phone'], ParentType, ContextType>;
  provider?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactFundAccountsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactFundAccountsResponse'] = ResolversParentTypes['MerchantContactFundAccountsResponse'],
> = {
  fundAccounts?: Resolver<
    Maybe<Array<ResolversTypes['MerchantContactFundAccount']>>,
    ParentType,
    ContextType
  >;
  hasMore?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactPersonResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactPerson'] = ResolversParentTypes['MerchantContactPerson'],
> = {
  email?: Resolver<ResolversTypes['MerchantEmailField'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  phone?: Resolver<ResolversTypes['MerchantPhoneField'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactTypeCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactTypeCreateResponse'] = ResolversParentTypes['MerchantContactTypeCreateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantContactTypeCreateResponseDuplicate' | 'MerchantContactTypeCreateResponseSuccess',
    ParentType,
    ContextType
  >;
};

export type MerchantContactTypeCreateResponseDuplicateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactTypeCreateResponseDuplicate'] = ResolversParentTypes['MerchantContactTypeCreateResponseDuplicate'],
> = {
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactTypeCreateResponseSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactTypeCreateResponseSuccess'] = ResolversParentTypes['MerchantContactTypeCreateResponseSuccess'],
> = {
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactUpdateResponse'] = ResolversParentTypes['MerchantContactUpdateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  merchantContact?: Resolver<ResolversTypes['MerchantContact'], ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantContactsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantContactsResponse'] = ResolversParentTypes['MerchantContactsResponse'],
> = {
  hasMore?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  merchantContacts?: Resolver<Array<ResolversTypes['MerchantContact']>, ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantCreditBalanceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantCreditBalance'] = ResolversParentTypes['MerchantCreditBalance'],
> = {
  amountCredits?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  balanceId?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  feeCredits?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  refundCredits?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  updatedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantCreditBalanceFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantCreditBalanceFailureResponse'] = ResolversParentTypes['MerchantCreditBalanceFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantCreditBalanceResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantCreditBalanceResponse'] = ResolversParentTypes['MerchantCreditBalanceResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantCreditBalanceFailureResponse' | 'MerchantCreditBalanceSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantCreditBalanceSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantCreditBalanceSuccessResponse'] = ResolversParentTypes['MerchantCreditBalanceSuccessResponse'],
> = {
  balanceDetails?: Resolver<ResolversTypes['MerchantCreditBalance'], ParentType, ContextType>;
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantDocumentResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantDocument'] = ResolversParentTypes['MerchantDocument'],
> = {
  aadharBack?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  aadharFront?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  affiliationCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  amfiCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  ayushCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  bankStatement?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  bankVerificationLetter?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  barCouncilCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  bbpsDocument?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  bisCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  boardResolutionLetter?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  brandTieUpDocument?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  businessCorrespondentDocument?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  businessPan?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  businessRegistration?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  cancelledCheque?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  cancelledChequeVideo?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  copywriteLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  cpvReport?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  dealershipRightsCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  dgcaCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  domainOwnershipDocument?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  dotCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  driverLicenseBack?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  driverLicenseFront?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  epfSchemeCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  fdaCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  fdaLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  ffmcLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  form8a?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  form10ac?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  form12a?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  form80g?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  form2020b2121b?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  fssaiCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  fssaiLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  giaCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  giiCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  govtAuthorisationLetter?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  gstCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  iataCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  iatoLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  iecLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  invoice?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  irctcAgentAgreement?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  irdaCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  irdaiRegistrationCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  legalOpinionDocument?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  liquorLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  manufacturingLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  merchantServiceAgreement?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  mmtcPampLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  msmeCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  msoDocument?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  nationalHousingBankCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  nbfcRegistrationCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  passportBack?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  passportFront?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  pciDssCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  personalPan?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  pescoLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  pesoLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  pharmacyDrugLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  pmWaniCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  ppiLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  proofOfProfession?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  rbiCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  reraLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  resellerAgreement?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  safegoldPartnershipDocument?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  sebiRegistrationCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  shopEstablishmentCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  slaAmfiCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  slaDealershipAgreement?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  slaDocument?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  slaFfmcLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  slaIataCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  slaIrdaiRegistrationCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  slaNbfcRegistrationCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  slaSebiRegistrationCertificate?: Resolver<
    ResolversTypes['MerchantDocumentField'],
    ParentType,
    ContextType
  >;
  tradeLicense?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  traiCertificate?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  undertaking?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  undertakingDocument?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  voterIdBack?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  voterIdFront?: Resolver<ResolversTypes['MerchantDocumentField'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantDocumentByIdResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantDocumentByIdResponse'] = ResolversParentTypes['MerchantDocumentByIdResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  fileName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  signedUrl?: Resolver<Maybe<ResolversTypes['URL']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantDocumentFieldResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantDocumentField'] = ResolversParentTypes['MerchantDocumentField'],
> = {
  clarificationReasons?: Resolver<
    Array<ResolversTypes['MerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  values?: Resolver<Array<ResolversTypes['MerchantDocumentFieldValue']>, ParentType, ContextType>;
  verificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantDocumentFieldValueResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantDocumentFieldValue'] = ResolversParentTypes['MerchantDocumentFieldValue'],
> = {
  createdAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  fileName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  url?: Resolver<Maybe<ResolversTypes['URL']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantDocumentFieldValueInterfaceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantDocumentFieldValueInterface'] = ResolversParentTypes['MerchantDocumentFieldValueInterface'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantDocumentByIdResponse' | 'MerchantDocumentFieldValue',
    ParentType,
    ContextType
  >;
  createdAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  fileName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
};

export type MerchantDocumentUploadResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantDocumentUpload'] = ResolversParentTypes['MerchantDocumentUpload'],
> = {
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  displayName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  mimeType?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  purpose?: Resolver<ResolversTypes['MerchantDocumentUploadPurposeEnum'], ParentType, ContextType>;
  size?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantDocumentUploadFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantDocumentUploadFailureResponse'] = ResolversParentTypes['MerchantDocumentUploadFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantDocumentUploadResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantDocumentUploadResponse'] = ResolversParentTypes['MerchantDocumentUploadResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantDocumentUploadFailureResponse' | 'MerchantDocumentUploadSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantDocumentUploadSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantDocumentUploadSuccessResponse'] = ResolversParentTypes['MerchantDocumentUploadSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  documentUpload?: Resolver<ResolversTypes['MerchantDocumentUpload'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantEmailFieldResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantEmailField'] = ResolversParentTypes['MerchantEmailField'],
> = {
  clarificationReasons?: Resolver<
    Array<ResolversTypes['MerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  value?: Resolver<Maybe<ResolversTypes['EmailAddress']>, ParentType, ContextType>;
  verificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantEmailUpdateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantEmailUpdateFailureResponse'] = ResolversParentTypes['MerchantEmailUpdateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantEmailUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantEmailUpdateResponse'] = ResolversParentTypes['MerchantEmailUpdateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantEmailUpdateFailureResponse' | 'MerchantEmailUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantEmailUpdateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantEmailUpdateSuccessResponse'] = ResolversParentTypes['MerchantEmailUpdateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isExistingUser?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isOwner?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isTeamMember?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantEscalationActionResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantEscalationAction'] = ResolversParentTypes['MerchantEscalationAction'],
> = {
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  status?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantEscalationLimitResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantEscalationLimit'] = ResolversParentTypes['MerchantEscalationLimit'],
> = {
  milestone?: Resolver<ResolversTypes['MerchantActivationMilestoneEnum'], ParentType, ContextType>;
  threshold?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantEscalationsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantEscalations'] = ResolversParentTypes['MerchantEscalations'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantActivationEscalationsBreached' | 'MerchantActivationEscalationsNotBreached',
    ParentType,
    ContextType
  >;
};

export type MerchantFeatureFlagResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantFeatureFlag'] = ResolversParentTypes['MerchantFeatureFlag'],
> = {
  displayText?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  isEnabled?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantFeeBasedGatingResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantFeeBasedGating'] = ResolversParentTypes['MerchantFeeBasedGating'],
> = {
  isEligible?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  orderId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  paymentStatus?: Resolver<
    Maybe<ResolversTypes['FeeBasedGatingPaymentStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantFieldClarificationReasonResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantFieldClarificationReason'] = ResolversParentTypes['MerchantFieldClarificationReason'],
> = {
  comment?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  isCurrentClarification?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  sender?: Resolver<ResolversTypes['MerchantClarificationFromEnum'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['MerchantClarificationTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantFieldInterfaceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantFieldInterface'] = ResolversParentTypes['MerchantFieldInterface'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantAverageOrderField'
    | 'MerchantBusinessTypeField'
    | 'MerchantDocumentField'
    | 'MerchantEmailField'
    | 'MerchantNumberField'
    | 'MerchantPhoneField'
    | 'MerchantStringField'
    | 'MerchantURLField',
    ParentType,
    ContextType
  >;
  clarificationReasons?: Resolver<
    Array<ResolversTypes['MerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  verificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
};

export type MerchantGstinUpdateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantGstinUpdate'] = ResolversParentTypes['MerchantGstinUpdate'],
> = {
  gstin?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  gstinCertificateFileId?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  gstinCertificateId?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  isGstinAddOperation?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isSyncFlow?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isWorkFlowCreated?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  validationId?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantGstinUpdateAsyncFlowSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantGstinUpdateAsyncFlowSuccessResponse'] = ResolversParentTypes['MerchantGstinUpdateAsyncFlowSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  gstinUpdate?: Resolver<ResolversTypes['MerchantGstinUpdate'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantGstinUpdateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantGstinUpdateFailureResponse'] = ResolversParentTypes['MerchantGstinUpdateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantGstinUpdateInSyncFlowResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantGstinUpdateInSyncFlowResponse'] = ResolversParentTypes['MerchantGstinUpdateInSyncFlowResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  gstinUpdate?: Resolver<ResolversTypes['MerchantGstinUpdate'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantGstinUpdateInSyncWorkFlowCreatedResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantGstinUpdateInSyncWorkFlowCreatedResponse'] = ResolversParentTypes['MerchantGstinUpdateInSyncWorkFlowCreatedResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  gstinUpdate?: Resolver<ResolversTypes['MerchantGstinUpdate'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantGstinUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantGstinUpdateResponse'] = ResolversParentTypes['MerchantGstinUpdateResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantGstinUpdateAsyncFlowSuccessResponse'
    | 'MerchantGstinUpdateFailureResponse'
    | 'MerchantGstinUpdateInSyncFlowResponse'
    | 'MerchantGstinUpdateInSyncWorkFlowCreatedResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantIdentityResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantIdentityResponse'] = ResolversParentTypes['MerchantIdentityResponse'],
> = {
  businessName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  number?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['MerchantIdentityTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantKycPartnerAccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantKYCPartnerAccessResponse'] = ResolversParentTypes['MerchantKYCPartnerAccessResponse'],
> = {
  partnerName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  status?: Resolver<ResolversTypes['MerchantKYCPartnerAccessTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantKycPartnerAccessStatusUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantKYCPartnerAccessStatusUpdateResponse'] = ResolversParentTypes['MerchantKYCPartnerAccessStatusUpdateResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantKYCPartnerAccessUpdateFailureResponse'
    | 'MerchantKYCPartnerAccessUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantKycPartnerAccessUpdateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantKYCPartnerAccessUpdateFailureResponse'] = ResolversParentTypes['MerchantKYCPartnerAccessUpdateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<
    Maybe<ResolversTypes['MerchantKYCPartnerAccessErrorTypeEnum']>,
    ParentType,
    ContextType
  >;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantKycPartnerAccessUpdateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantKYCPartnerAccessUpdateSuccessResponse'] = ResolversParentTypes['MerchantKYCPartnerAccessUpdateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantNameResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantName'] = ResolversParentTypes['MerchantName'],
> = {
  billing?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  display?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  registered?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantNcEligibilityResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantNcEligibilityResponse'] = ResolversParentTypes['MerchantNcEligibilityResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isNeedsClarificationRevampEnabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantNumberFieldResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantNumberField'] = ResolversParentTypes['MerchantNumberField'],
> = {
  clarificationReasons?: Resolver<
    Array<ResolversTypes['MerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  value?: Resolver<Maybe<ResolversTypes['PositiveInt']>, ParentType, ContextType>;
  verificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantOnboardingConfigResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantOnboardingConfig'] = ResolversParentTypes['MerchantOnboardingConfig'],
> = {
  configData?: Resolver<ResolversTypes['ConfigData'], ParentType, ContextType>;
  configType?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantOnboardingQuestionDetailResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantOnboardingQuestionDetail'] = ResolversParentTypes['MerchantOnboardingQuestionDetail'],
> = {
  answer?: Resolver<Array<ResolversTypes['String']>, ParentType, ContextType>;
  questionId?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantOnboardingQuestionDetailsFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantOnboardingQuestionDetailsFailureResponse'] = ResolversParentTypes['MerchantOnboardingQuestionDetailsFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantOnboardingQuestionDetailsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantOnboardingQuestionDetailsResponse'] = ResolversParentTypes['MerchantOnboardingQuestionDetailsResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantOnboardingQuestionDetailsFailureResponse'
    | 'MerchantOnboardingQuestionDetailsSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantOnboardingQuestionDetailsSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantOnboardingQuestionDetailsSuccessResponse'] = ResolversParentTypes['MerchantOnboardingQuestionDetailsSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  questionDetails?: Resolver<
    Array<ResolversTypes['MerchantOnboardingQuestionDetail']>,
    ParentType,
    ContextType
  >;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantOnboardingQuestionDetailsUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantOnboardingQuestionDetailsUpdateResponse'] = ResolversParentTypes['MerchantOnboardingQuestionDetailsUpdateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentAcceptanceChannelsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentAcceptanceChannels'] = ResolversParentTypes['MerchantPaymentAcceptanceChannels'],
> = {
  android?: Resolver<ResolversTypes['MerchantAcceptanceChannel'], ParentType, ContextType>;
  ios?: Resolver<ResolversTypes['MerchantAcceptanceChannel'], ParentType, ContextType>;
  offlineStore?: Resolver<ResolversTypes['MerchantAcceptanceChannel'], ParentType, ContextType>;
  others?: Resolver<ResolversTypes['MerchantAcceptanceChannel'], ParentType, ContextType>;
  socialMedia?: Resolver<ResolversTypes['MerchantAcceptanceChannel'], ParentType, ContextType>;
  websites?: Resolver<ResolversTypes['MerchantAcceptanceChannel'], ParentType, ContextType>;
  whatsappSmsEmail?: Resolver<
    ResolversTypes['MerchantAcceptanceChannelWhatsappSmsEmail'],
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandle'] = ResolversParentTypes['MerchantPaymentHandle'],
> = {
  paymentHandleSlug?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  paymentPageId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  title?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  url?: Resolver<ResolversTypes['URL'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleAvailabilityFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleAvailabilityFailureResponse'] = ResolversParentTypes['MerchantPaymentHandleAvailabilityFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleAvailabilityResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleAvailabilityResponse'] = ResolversParentTypes['MerchantPaymentHandleAvailabilityResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantPaymentHandleAvailabilityFailureResponse'
    | 'MerchantPaymentHandleAvailabilitySuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantPaymentHandleAvailabilitySuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleAvailabilitySuccessResponse'] = ResolversParentTypes['MerchantPaymentHandleAvailabilitySuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isPaymentHandleAvailable?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleCreateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleCreateFailureResponse'] = ResolversParentTypes['MerchantPaymentHandleCreateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleCreateResponse'] = ResolversParentTypes['MerchantPaymentHandleCreateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantPaymentHandleCreateFailureResponse' | 'MerchantPaymentHandleCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantPaymentHandleCreateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleCreateSuccessResponse'] = ResolversParentTypes['MerchantPaymentHandleCreateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  paymentHandle?: Resolver<ResolversTypes['MerchantPaymentHandle'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleEncryptedAmountFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleEncryptedAmountFailureResponse'] = ResolversParentTypes['MerchantPaymentHandleEncryptedAmountFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleEncryptedAmountResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleEncryptedAmountResponse'] = ResolversParentTypes['MerchantPaymentHandleEncryptedAmountResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantPaymentHandleEncryptedAmountFailureResponse'
    | 'MerchantPaymentHandleEncryptedAmountSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantPaymentHandleEncryptedAmountSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleEncryptedAmountSuccessResponse'] = ResolversParentTypes['MerchantPaymentHandleEncryptedAmountSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  encryptedAmount?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleFailureResponse'] = ResolversParentTypes['MerchantPaymentHandleFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleResponse'] = ResolversParentTypes['MerchantPaymentHandleResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantPaymentHandleFailureResponse' | 'MerchantPaymentHandleSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantPaymentHandleSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleSuccessResponse'] = ResolversParentTypes['MerchantPaymentHandleSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  paymentHandle?: Resolver<ResolversTypes['MerchantPaymentHandle'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleSuggestionsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleSuggestionsResponse'] = ResolversParentTypes['MerchantPaymentHandleSuggestionsResponse'],
> = {
  suggestions?: Resolver<Array<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleUpdateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleUpdateFailureResponse'] = ResolversParentTypes['MerchantPaymentHandleUpdateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPaymentHandleUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleUpdateResponse'] = ResolversParentTypes['MerchantPaymentHandleUpdateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantPaymentHandleUpdateFailureResponse' | 'MerchantPaymentHandleUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantPaymentHandleUpdateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPaymentHandleUpdateSuccessResponse'] = ResolversParentTypes['MerchantPaymentHandleUpdateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  paymentHandle?: Resolver<ResolversTypes['MerchantPaymentHandle'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPhoneFieldResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPhoneField'] = ResolversParentTypes['MerchantPhoneField'],
> = {
  clarificationReasons?: Resolver<
    Array<ResolversTypes['MerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  value?: Resolver<ResolversTypes['Phone'], ParentType, ContextType>;
  verificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyEmptyPreviewResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyEmptyPreviewResponse'] = ResolversParentTypes['MerchantPolicyEmptyPreviewResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyEmptyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyEmptyResponse'] = ResolversParentTypes['MerchantPolicyEmptyResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyEmptyV2PreviewResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyEmptyV2PreviewResponse'] = ResolversParentTypes['MerchantPolicyEmptyV2PreviewResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyFailureResponse'] = ResolversParentTypes['MerchantPolicyFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyPreviewResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyPreview'] = ResolversParentTypes['MerchantPolicyPreview'],
> = {
  html?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  section?: Resolver<ResolversTypes['MerchantWebsiteSectionEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyPreviewFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyPreviewFailureResponse'] = ResolversParentTypes['MerchantPolicyPreviewFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyPreviewResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyPreviewResponse'] = ResolversParentTypes['MerchantPolicyPreviewResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantPolicyEmptyPreviewResponse'
    | 'MerchantPolicyPreviewFailureResponse'
    | 'MerchantPolicyPreviewSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantPolicyPreviewSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyPreviewSuccessResponse'] = ResolversParentTypes['MerchantPolicyPreviewSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  policyPreview?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyPreviewV2FailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyPreviewV2FailureResponse'] = ResolversParentTypes['MerchantPolicyPreviewV2FailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyPreviewV2ResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyPreviewV2Response'] = ResolversParentTypes['MerchantPolicyPreviewV2Response'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantPolicyEmptyV2PreviewResponse'
    | 'MerchantPolicyPreviewV2FailureResponse'
    | 'MerchantPolicyPreviewV2SuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantPolicyPreviewV2SuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyPreviewV2SuccessResponse'] = ResolversParentTypes['MerchantPolicyPreviewV2SuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  policyPreview?: Resolver<Array<ResolversTypes['MerchantPolicyPreview']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyPublishFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyPublishFailureResponse'] = ResolversParentTypes['MerchantPolicyPublishFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyPublishResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyPublishResponse'] = ResolversParentTypes['MerchantPolicyPublishResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantPolicyPublishFailureResponse' | 'MerchantPolicyPublishSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantPolicyPublishSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyPublishSuccessResponse'] = ResolversParentTypes['MerchantPolicyPublishSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  merchantWebsite?: Resolver<ResolversTypes['MerchantWebsite'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyResponse'] = ResolversParentTypes['MerchantPolicyResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantPolicyEmptyResponse'
    | 'MerchantPolicyFailureResponse'
    | 'MerchantPolicySuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantPolicySuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicySuccessResponse'] = ResolversParentTypes['MerchantPolicySuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  policy?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPolicyWizardV2EligibilityResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPolicyWizardV2EligibilityResponse'] = ResolversParentTypes['MerchantPolicyWizardV2EligibilityResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isPolicyWizardV2Enabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantPreferenceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantPreference'] = ResolversParentTypes['MerchantPreference'],
> = {
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  group?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  merchantId?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  productType?: Resolver<
    ResolversTypes['MerchantPreferenceProductTypeEnum'],
    ParentType,
    ContextType
  >;
  type?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  updatedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  value?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantReferralFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantReferralFailureResponse'] = ResolversParentTypes['MerchantReferralFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantReferralResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantReferralResponse'] = ResolversParentTypes['MerchantReferralResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantReferralFailureResponse' | 'MerchantReferralSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantReferralSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantReferralSuccessResponse'] = ResolversParentTypes['MerchantReferralSuccessResponse'],
> = {
  canRefer?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  maxAllowedReferrals?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  referralAmount?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  referralLink?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantSelfServeWorkflowResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSelfServeWorkflow'] = ResolversParentTypes['MerchantSelfServeWorkflow'],
> = {
  bankAccountId?: Resolver<Maybe<ResolversTypes['ID']>, ParentType, ContextType>;
  createdAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  customerActions?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['MerchantGstinUpdateCustomerActionEnum']>>>,
    ParentType,
    ContextType
  >;
  isRequestUnderBvsValidation?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isWorkflowExits?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  needsClarificationMessage?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  permission?: Resolver<
    Maybe<ResolversTypes['MerchantSelfServeGstinPermissionEnum']>,
    ParentType,
    ContextType
  >;
  rejectedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  rejectionReason?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  workflowStatus?: Resolver<
    Maybe<ResolversTypes['MerchantGstinWorkflowStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantSelfServeWorkflowStatusFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSelfServeWorkflowStatusFailureResponse'] = ResolversParentTypes['MerchantSelfServeWorkflowStatusFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantSelfServeWorkflowStatusResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSelfServeWorkflowStatusResponse'] = ResolversParentTypes['MerchantSelfServeWorkflowStatusResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantSelfServeWorkflowStatusFailureResponse'
    | 'MerchantSelfServeWorkflowStatusSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantSelfServeWorkflowStatusSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSelfServeWorkflowStatusSuccessResponse'] = ResolversParentTypes['MerchantSelfServeWorkflowStatusSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  selfServeWorkflow?: Resolver<
    ResolversTypes['MerchantSelfServeWorkflow'],
    ParentType,
    ContextType
  >;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantSettlementConfigFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSettlementConfigFailureResponse'] = ResolversParentTypes['MerchantSettlementConfigFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantSettlementConfigResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSettlementConfigResponse'] = ResolversParentTypes['MerchantSettlementConfigResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantSettlementConfigFailureResponse' | 'MerchantSettlementConfigSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantSettlementConfigSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSettlementConfigSuccessResponse'] = ResolversParentTypes['MerchantSettlementConfigSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  fundsOnHold?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  settlementBlocked?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  settlementsOnHold?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantShopEstablishmentResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantShopEstablishment'] = ResolversParentTypes['MerchantShopEstablishment'],
> = {
  isVerifiableZone?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  number?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantSocialMediaUrlFieldResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSocialMediaURLField'] = ResolversParentTypes['MerchantSocialMediaURLField'],
> = {
  platform?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  url?: Resolver<Maybe<ResolversTypes['URL']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantStakeholderResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantStakeholder'] = ResolversParentTypes['MerchantStakeholder'],
> = {
  aadharEsignStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  aadharPin?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  isAadharLinked?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  name?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  pan?: Resolver<ResolversTypes['MerchantStringField'], ParentType, ContextType>;
  panVerificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantStringFieldResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantStringField'] = ResolversParentTypes['MerchantStringField'],
> = {
  clarificationReasons?: Resolver<
    Array<ResolversTypes['MerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  suggestedValue?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  value?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  verificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantSupportDetailsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSupportDetails'] = ResolversParentTypes['MerchantSupportDetails'],
> = {
  email?: Resolver<Maybe<ResolversTypes['EmailAddress']>, ParentType, ContextType>;
  phone?: Resolver<Maybe<ResolversTypes['Phone']>, ParentType, ContextType>;
  websiteUrl?: Resolver<Maybe<ResolversTypes['URL']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantSupportDetailsFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSupportDetailsFailureResponse'] = ResolversParentTypes['MerchantSupportDetailsFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantSupportDetailsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSupportDetailsResponse'] = ResolversParentTypes['MerchantSupportDetailsResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantSupportDetailsFailureResponse' | 'MerchantSupportDetailsSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantSupportDetailsSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSupportDetailsSuccessResponse'] = ResolversParentTypes['MerchantSupportDetailsSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  details?: Resolver<Maybe<ResolversTypes['MerchantSupportDetails']>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantSwitchResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantSwitchResponse'] = ResolversParentTypes['MerchantSwitchResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantTransactionLimitResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantTransactionLimit'] = ResolversParentTypes['MerchantTransactionLimit'],
> = {
  paymentLimit?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  settlementLimit?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantUrlFieldResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantURLField'] = ResolversParentTypes['MerchantURLField'],
> = {
  clarificationReasons?: Resolver<
    Array<ResolversTypes['MerchantFieldClarificationReason']>,
    ParentType,
    ContextType
  >;
  value?: Resolver<Maybe<ResolversTypes['URL']>, ParentType, ContextType>;
  verificationStatus?: Resolver<
    Maybe<ResolversTypes['MerchantVerificationStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantValidateSocialMediaUrlResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantValidateSocialMediaURLResponse'] = ResolversParentTypes['MerchantValidateSocialMediaURLResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantVirtualAccountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantVirtualAccount'] = ResolversParentTypes['MerchantVirtualAccount'],
> = {
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  receivers?: Resolver<
    Array<ResolversTypes['MerchantVirtualAccountReceiver']>,
    ParentType,
    ContextType
  >;
  status?: Resolver<ResolversTypes['MerchantVirtualAccountStatus'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantVirtualAccountReceiverResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantVirtualAccountReceiver'] = ResolversParentTypes['MerchantVirtualAccountReceiver'],
> = {
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  ifsc?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  number?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantVirtualAccountsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantVirtualAccountsResponse'] = ResolversParentTypes['MerchantVirtualAccountsResponse'],
> = {
  hasMore?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  virtualAccounts?: Resolver<
    Array<ResolversTypes['MerchantVirtualAccount']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsite'] = ResolversParentTypes['MerchantWebsite'],
> = {
  additionalData?: Resolver<
    Maybe<ResolversTypes['MerchantWebsiteAdditionalData']>,
    ParentType,
    ContextType
  >;
  contactUs?: Resolver<Maybe<ResolversTypes['MerchantWebsiteSection']>, ParentType, ContextType>;
  privacy?: Resolver<Maybe<ResolversTypes['MerchantWebsiteSection']>, ParentType, ContextType>;
  refund?: Resolver<Maybe<ResolversTypes['MerchantWebsiteSection']>, ParentType, ContextType>;
  shipping?: Resolver<Maybe<ResolversTypes['MerchantWebsiteSection']>, ParentType, ContextType>;
  status?: Resolver<
    Maybe<ResolversTypes['MerchantWebsiteApprovalStatusEnum']>,
    ParentType,
    ContextType
  >;
  termsAndConditions?: Resolver<
    Maybe<ResolversTypes['MerchantWebsiteSection']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteAdditionalDataResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteAdditionalData'] = ResolversParentTypes['MerchantWebsiteAdditionalData'],
> = {
  contactEmail?: Resolver<Maybe<ResolversTypes['EmailAddress']>, ParentType, ContextType>;
  contactSupportNumber?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  refundProcessPeriod?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  refundRequestPeriod?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  shippingPeriod?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteApplicationDetailResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteApplicationDetail'] = ResolversParentTypes['MerchantWebsiteApplicationDetail'],
> = {
  documentId?: Resolver<Maybe<ResolversTypes['ID']>, ParentType, ContextType>;
  host?: Resolver<ResolversTypes['URL'], ParentType, ContextType>;
  signedUrl?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  url?: Resolver<Maybe<ResolversTypes['URL']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteDetailsFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteDetailsFailureResponse'] = ResolversParentTypes['MerchantWebsiteDetailsFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteDetailsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteDetailsResponse'] = ResolversParentTypes['MerchantWebsiteDetailsResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  merchantWebsite?: Resolver<ResolversTypes['MerchantWebsite'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteDocumentDeleteFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteDocumentDeleteFailureResponse'] = ResolversParentTypes['MerchantWebsiteDocumentDeleteFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteDocumentDeleteResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteDocumentDeleteResponse'] = ResolversParentTypes['MerchantWebsiteDocumentDeleteResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantWebsiteDocumentDeleteFailureResponse' | 'MerchantWebsiteDocumentDeleteSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantWebsiteDocumentDeleteSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteDocumentDeleteSuccessResponse'] = ResolversParentTypes['MerchantWebsiteDocumentDeleteSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  merchantWebsite?: Resolver<ResolversTypes['MerchantWebsite'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteDocumentUploadFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteDocumentUploadFailureResponse'] = ResolversParentTypes['MerchantWebsiteDocumentUploadFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteDocumentUploadResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteDocumentUploadResponse'] = ResolversParentTypes['MerchantWebsiteDocumentUploadResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantWebsiteDocumentUploadFailureResponse' | 'MerchantWebsiteDocumentUploadSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantWebsiteDocumentUploadSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteDocumentUploadSuccessResponse'] = ResolversParentTypes['MerchantWebsiteDocumentUploadSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  merchantWebsite?: Resolver<ResolversTypes['MerchantWebsite'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsitePublishFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsitePublishFailureResponse'] = ResolversParentTypes['MerchantWebsitePublishFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsitePublishResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsitePublishResponse'] = ResolversParentTypes['MerchantWebsitePublishResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantWebsitePublishFailureResponse' | 'MerchantWebsitePublishSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantWebsitePublishSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsitePublishSuccessResponse'] = ResolversParentTypes['MerchantWebsitePublishSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  merchantWebsite?: Resolver<ResolversTypes['MerchantWebsite'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteSectionResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteSection'] = ResolversParentTypes['MerchantWebsiteSection'],
> = {
  appStore?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['MerchantWebsiteApplicationDetail']>>>,
    ParentType,
    ContextType
  >;
  playStore?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['MerchantWebsiteApplicationDetail']>>>,
    ParentType,
    ContextType
  >;
  publishedUrl?: Resolver<Maybe<ResolversTypes['URL']>, ParentType, ContextType>;
  updatedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  website?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['MerchantWebsiteApplicationDetail']>>>,
    ParentType,
    ContextType
  >;
  websiteApprovalStatus?: Resolver<
    Maybe<ResolversTypes['MerchantWebsiteApprovalStatusEnum']>,
    ParentType,
    ContextType
  >;
  websiteSectionStatus?: Resolver<
    Maybe<ResolversTypes['MerchantWebsiteSectionStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsiteTermsAndConditionsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsiteTermsAndConditions'] = ResolversParentTypes['MerchantWebsiteTermsAndConditions'],
> = {
  deliverableType?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  link?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  refundProcessPeriod?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  refundRequestPeriod?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  shippingPeriod?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  supportEmail?: Resolver<Maybe<ResolversTypes['EmailAddress']>, ParentType, ContextType>;
  warrantyPeriod?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWebsitesResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWebsitesResponse'] = ResolversParentTypes['MerchantWebsitesResponse'],
> = {
  __resolveType: TypeResolveFn<
    'MerchantWebsiteDetailsFailureResponse' | 'MerchantWebsiteDetailsResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantWorkflowClarificationSubmitFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWorkflowClarificationSubmitFailureResponse'] = ResolversParentTypes['MerchantWorkflowClarificationSubmitFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantWorkflowClarificationSubmitResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWorkflowClarificationSubmitResponse'] = ResolversParentTypes['MerchantWorkflowClarificationSubmitResponse'],
> = {
  __resolveType: TypeResolveFn<
    | 'MerchantWorkflowClarificationSubmitFailureResponse'
    | 'MerchantWorkflowClarificationSubmitSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type MerchantWorkflowClarificationSubmitSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MerchantWorkflowClarificationSubmitSuccessResponse'] = ResolversParentTypes['MerchantWorkflowClarificationSubmitSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MoneyResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Money'] = ResolversParentTypes['Money'],
> = {
  currency?: Resolver<ResolversTypes['Currency'], ParentType, ContextType>;
  value?: Resolver<ResolversTypes['BigInt'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MutationResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Mutation'] = ResolversParentTypes['Mutation'],
> = {
  aadhaarCaptchaVerify?: Resolver<
    ResolversTypes['AadhaarCaptchaVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationAadhaarCaptchaVerifyArgs, 'aadhaarNumber' | 'captcha'>
  >;
  aadhaarDigilockerOtp?: Resolver<
    ResolversTypes['AadhaarDigilockerOtpResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationAadhaarDigilockerOtpArgs, 'aadhaarNumber'>
  >;
  aadhaarDigilockerRedirectionUrl?: Resolver<
    ResolversTypes['AadhaarDigilockerRedirectionUrlResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationAadhaarDigilockerRedirectionUrlArgs, 'redirectUrl' | 'verificationType'>
  >;
  aadhaarDigilockerRedirectionUrlVerify?: Resolver<
    ResolversTypes['AadhaarDigilockerRedirectionUrlVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationAadhaarDigilockerRedirectionUrlVerifyArgs, 'verificationType'>
  >;
  aadhaarOtpVerify?: Resolver<
    ResolversTypes['AadhaarOtpVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationAadhaarOtpVerifyArgs, 'captcha' | 'filePassword' | 'otp'>
  >;
  aadharDigilockerOtpVerify?: Resolver<
    ResolversTypes['AadhaarDigilockerOtpVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationAadharDigilockerOtpVerifyArgs, 'aadhaarNumber' | 'otp' | 'requestId'>
  >;
  accountVerificationOtp?: Resolver<
    ResolversTypes['AccountVerificationOtpResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationAccountVerificationOtpArgs, 'password'>
  >;
  accountVerificationOtpResend?: Resolver<
    ResolversTypes['AccountVerificationOtpResendResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationAccountVerificationOtpResendArgs, 'password' | 'token'>
  >;
  accountVerify?: Resolver<
    ResolversTypes['Auth'],
    ParentType,
    ContextType,
    RequireFields<MutationAccountVerifyArgs, 'otp' | 'token'>
  >;
  approveIciciPayout?: Resolver<
    ResolversTypes['ApproveIciciPayoutResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationApproveIciciPayoutArgs, 'id' | 'otp'>
  >;
  approvePayout?: Resolver<
    ResolversTypes['ApprovePayoutResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationApprovePayoutArgs, 'id' | 'otp' | 'queueOnLowBalance' | 'token'>
  >;
  approvePayoutBatch?: Resolver<
    ResolversTypes['ApprovePayoutBatchResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationApprovePayoutBatchArgs, 'batchIds' | 'otp' | 'token'>
  >;
  couponApply?: Resolver<ResolversTypes['CouponApplyResponse'], ParentType, ContextType>;
  couponValidate?: Resolver<
    ResolversTypes['CouponValidateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationCouponValidateArgs, 'code'>
  >;
  deregisterFCMToken?: Resolver<
    ResolversTypes['DeregisterFCMTokenResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationDeregisterFcmTokenArgs, 'productType' | 'tokenIdentifier'>
  >;
  loginEmail?: Resolver<
    ResolversTypes['Auth'],
    ParentType,
    ContextType,
    RequireFields<MutationLoginEmailArgs, 'email' | 'password'>
  >;
  loginEmailVerify?: Resolver<
    ResolversTypes['Auth'],
    ParentType,
    ContextType,
    RequireFields<MutationLoginEmailVerifyArgs, 'otp' | 'token'>
  >;
  loginOAuth?: Resolver<
    ResolversTypes['Auth'],
    ParentType,
    ContextType,
    RequireFields<MutationLoginOAuthArgs, 'email' | 'idToken' | 'platform' | 'provider'>
  >;
  loginOtp?: Resolver<
    ResolversTypes['LoginOtpResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationLoginOtpArgs, 'phone'>
  >;
  loginOtpResend?: Resolver<
    ResolversTypes['LoginOtpResendResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationLoginOtpResendArgs, 'phone'>
  >;
  loginOtpVerify?: Resolver<
    ResolversTypes['Auth'],
    ParentType,
    ContextType,
    RequireFields<MutationLoginOtpVerifyArgs, 'otp' | 'phone' | 'token'>
  >;
  loginTwoFactor?: Resolver<
    ResolversTypes['Auth'],
    ParentType,
    ContextType,
    RequireFields<MutationLoginTwoFactorArgs, 'code'>
  >;
  loginTwoFactorPassword?: Resolver<
    ResolversTypes['Auth'],
    ParentType,
    ContextType,
    RequireFields<MutationLoginTwoFactorPasswordArgs, 'password'>
  >;
  merchantActivationDetailsUpdate?: Resolver<
    ResolversTypes['MerchantActivationResponse'],
    ParentType,
    ContextType,
    Partial<MutationMerchantActivationDetailsUpdateArgs>
  >;
  merchantActivationDocumentDelete?: Resolver<
    ResolversTypes['MerchantActivationResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantActivationDocumentDeleteArgs, 'documentId'>
  >;
  merchantActivationDocumentUpload?: Resolver<
    ResolversTypes['MerchantActivationResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantActivationDocumentUploadArgs, 'file' | 'name'>
  >;
  merchantApiKeyCreate?: Resolver<
    ResolversTypes['MerchantApiKeyCreateResponse'],
    ParentType,
    ContextType
  >;
  merchantApiKeyRegenerate?: Resolver<
    ResolversTypes['MerchantApiKeyRegenerateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantApiKeyRegenerateArgs, 'apiKeyRegenerationDelayType' | 'oldApiKey'>
  >;
  merchantApiKeysCreate?: Resolver<
    ResolversTypes['MerchantApiKeysCreateResponse'],
    ParentType,
    ContextType
  >;
  merchantBankAccountDocumentUpload?: Resolver<
    ResolversTypes['MerchantBankAccountDocumentUploadResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantBankAccountDocumentUploadArgs, 'document'>
  >;
  merchantBankAccountUpdate?: Resolver<
    ResolversTypes['MerchantBankAccountUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<
      MutationMerchantBankAccountUpdateArgs,
      'accountNumber' | 'beneficiaryName' | 'ifscCode'
    >
  >;
  merchantBusinessAppDetailsUpdate?: Resolver<
    ResolversTypes['MerchantBusinessAppDetailsResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantBusinessAppDetailsUpdateArgs, 'businessApp'>
  >;
  merchantBusinessWebsiteDetailsUpdate?: Resolver<
    ResolversTypes['MerchantBusinessWebsiteDetailsResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantBusinessWebsiteDetailsUpdateArgs, 'businessWebsite'>
  >;
  merchantClarificationDetailsSubmit?: Resolver<
    ResolversTypes['MerchantClarificationDetailsSubmitResponse'],
    ParentType,
    ContextType,
    Partial<MutationMerchantClarificationDetailsSubmitArgs>
  >;
  merchantClarificationDetailsUpdate?: Resolver<
    ResolversTypes['MerchantClarificationDetailsUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantClarificationDetailsUpdateArgs, 'fieldName'>
  >;
  merchantConfigUpdate?: Resolver<
    ResolversTypes['MerchantConfigUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantConfigUpdateArgs, 'namespace'>
  >;
  merchantConfigurationUpdate?: Resolver<
    ResolversTypes['merchantConfigurationUpdateResponse'],
    ParentType,
    ContextType,
    Partial<MutationMerchantConfigurationUpdateArgs>
  >;
  merchantContactCreate?: Resolver<
    ResolversTypes['MerchantContactCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantContactCreateArgs, 'name'>
  >;
  merchantContactEmailOtpSend?: Resolver<
    ResolversTypes['MerchantContactEmailOtpSendResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantContactEmailOtpSendArgs, 'email'>
  >;
  merchantContactFundAccountCreate?: Resolver<
    ResolversTypes['MerchantContactFundAccountCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantContactFundAccountCreateArgs, 'contactId' | 'type'>
  >;
  merchantContactTypeCreate?: Resolver<
    ResolversTypes['MerchantContactTypeCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantContactTypeCreateArgs, 'type'>
  >;
  merchantContactUpdate?: Resolver<
    ResolversTypes['MerchantContactUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantContactUpdateArgs, 'id'>
  >;
  merchantDocumentUpload?: Resolver<
    ResolversTypes['MerchantDocumentUploadResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantDocumentUploadArgs, 'document' | 'purpose'>
  >;
  merchantEmailUpdate?: Resolver<
    ResolversTypes['MerchantEmailUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantEmailUpdateArgs, 'shouldUpdateContactEmail' | 'updatedEmail'>
  >;
  merchantGstinUpdate?: Resolver<
    ResolversTypes['MerchantGstinUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantGstinUpdateArgs, 'gstin' | 'gstinCertificate'>
  >;
  merchantKYCPartnerAccessUpdate?: Resolver<
    ResolversTypes['MerchantKYCPartnerAccessStatusUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantKycPartnerAccessUpdateArgs, 'referralCode' | 'status'>
  >;
  merchantOnboardingQuestionDetailsUpdate?: Resolver<
    ResolversTypes['MerchantOnboardingQuestionDetailsUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantOnboardingQuestionDetailsUpdateArgs, 'questionDetails'>
  >;
  merchantPaymentHandleCreate?: Resolver<
    ResolversTypes['MerchantPaymentHandleCreateResponse'],
    ParentType,
    ContextType
  >;
  merchantPaymentHandleEncryptedAmount?: Resolver<
    ResolversTypes['MerchantPaymentHandleEncryptedAmountResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantPaymentHandleEncryptedAmountArgs, 'amount'>
  >;
  merchantPaymentHandleUpdate?: Resolver<
    ResolversTypes['MerchantPaymentHandleUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantPaymentHandleUpdateArgs, 'paymentHandleSlug'>
  >;
  merchantPolicyPublish?: Resolver<
    ResolversTypes['MerchantPolicyPublishResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantPolicyPublishArgs, 'action' | 'section'>
  >;
  merchantStoreConsents?: Resolver<
    ResolversTypes['MerchantConsentsResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantStoreConsentsArgs, 'consents' | 'event'>
  >;
  merchantSwitch?: Resolver<
    ResolversTypes['MerchantSwitchResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantSwitchArgs, 'id'>
  >;
  merchantSwitchOAuth?: Resolver<
    ResolversTypes['MerchantSwitchResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantSwitchOAuthArgs, 'accessToken' | 'clientId' | 'id'>
  >;
  merchantWebsiteDetailsUpdate?: Resolver<
    ResolversTypes['MerchantWebsite'],
    ParentType,
    ContextType,
    Partial<MutationMerchantWebsiteDetailsUpdateArgs>
  >;
  merchantWebsiteDocumentDelete?: Resolver<
    ResolversTypes['MerchantWebsiteDocumentDeleteResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantWebsiteDocumentDeleteArgs, 'platform' | 'section'>
  >;
  merchantWebsiteDocumentUpload?: Resolver<
    ResolversTypes['MerchantWebsiteDocumentUploadResponse'],
    ParentType,
    ContextType,
    RequireFields<
      MutationMerchantWebsiteDocumentUploadArgs,
      'platform' | 'section' | 'websiteDocument'
    >
  >;
  merchantWebsitePublish?: Resolver<
    ResolversTypes['MerchantWebsitePublishResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationMerchantWebsitePublishArgs, 'action' | 'hasMerchantConsent' | 'section'>
  >;
  merchantWorkflowClarificationSubmit?: Resolver<
    ResolversTypes['MerchantWorkflowClarificationSubmitResponse'],
    ParentType,
    ContextType,
    RequireFields<
      MutationMerchantWorkflowClarificationSubmitArgs,
      'clarificationReason' | 'documentIds' | 'workflow'
    >
  >;
  notificationEmailUpdate?: Resolver<
    ResolversTypes['NotificationEmailUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationNotificationEmailUpdateArgs, 'transactionReportEmail'>
  >;
  notificationWhatsAppOptIn?: Resolver<
    ResolversTypes['NotificationWhatsAppOptIn'],
    ParentType,
    ContextType,
    RequireFields<MutationNotificationWhatsAppOptInArgs, 'source'>
  >;
  oAuthTokenAppleWatch?: Resolver<
    ResolversTypes['OauthTokenAppleWatchResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationOAuthTokenAppleWatchArgs, 'otp' | 'token'>
  >;
  oAuthTokenAppleWatchOtp?: Resolver<
    ResolversTypes['OauthTokenAppleWatchOtp'],
    ParentType,
    ContextType
  >;
  onboardingPaymentOrderCreate?: Resolver<
    ResolversTypes['OnboardingPaymentOrderCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationOnboardingPaymentOrderCreateArgs, 'createOrder'>
  >;
  onboardingPaymentOrderVerify?: Resolver<
    ResolversTypes['OnboardingPaymentOrderVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationOnboardingPaymentOrderVerifyArgs, 'orderId' | 'paymentId' | 'signature'>
  >;
  orderCreate?: Resolver<
    ResolversTypes['OrderCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationOrderCreateArgs, 'amount'>
  >;
  paymentCapture?: Resolver<
    ResolversTypes['PaymentCaptureResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationPaymentCaptureArgs, 'amount' | 'id'>
  >;
  paymentLinkCancel?: Resolver<
    ResolversTypes['PaymentLinkCancelResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationPaymentLinkCancelArgs, 'id'>
  >;
  paymentLinkCreate?: Resolver<
    ResolversTypes['PaymentLinkCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationPaymentLinkCreateArgs, 'amount'>
  >;
  paymentLinkNotify?: Resolver<
    ResolversTypes['PaymentLinkNotifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationPaymentLinkNotifyArgs, 'id' | 'medium'>
  >;
  paymentRefund?: Resolver<
    ResolversTypes['PaymentRefundResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationPaymentRefundArgs, 'amount' | 'id'>
  >;
  paymentsNewLaunchProductViewUpdate?: Resolver<
    ResolversTypes['PaymentsNewLaunchProductViewUpdate'],
    ParentType,
    ContextType
  >;
  paymentsProductFtuxUpdate?: Resolver<
    ResolversTypes['PaymentsProductFtuxUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationPaymentsProductFtuxUpdateArgs, 'isFtuxComplete' | 'product'>
  >;
  payoutApproveBulk?: Resolver<
    ResolversTypes['PayoutApproveBulkResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationPayoutApproveBulkArgs, 'ids' | 'otp' | 'queueOnLowBalance' | 'token'>
  >;
  payoutCompositeCreate?: Resolver<
    ResolversTypes['PayoutCompositeCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<
      MutationPayoutCompositeCreateArgs,
      | 'amount'
      | 'bankingAccountNumber'
      | 'fundAccountType'
      | 'merchantContact'
      | 'mode'
      | 'otp'
      | 'purpose'
      | 'queueOnLowBalance'
      | 'token'
      | 'vpa'
    >
  >;
  payoutCreate?: Resolver<
    ResolversTypes['PayoutCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<
      MutationPayoutCreateArgs,
      | 'amount'
      | 'bankingAccountNumber'
      | 'fundAccountId'
      | 'mode'
      | 'otp'
      | 'purpose'
      | 'queueOnLowBalance'
      | 'token'
    >
  >;
  payoutCreateIcici?: Resolver<
    ResolversTypes['PayoutCreateIciciResponse'],
    ParentType,
    ContextType,
    RequireFields<
      MutationPayoutCreateIciciArgs,
      'amount' | 'bankingAccountNumber' | 'fundAccountId' | 'mode' | 'purpose' | 'queueOnLowBalance'
    >
  >;
  payoutLinkCreate?: Resolver<
    ResolversTypes['PayoutLinkCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<
      MutationPayoutLinkCreateArgs,
      | 'amount'
      | 'bankingAccountNumber'
      | 'description'
      | 'merchantContactId'
      | 'otp'
      | 'purpose'
      | 'sendVia'
      | 'token'
    >
  >;
  payoutPurposeCreate?: Resolver<
    ResolversTypes['PayoutPurposeCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationPayoutPurposeCreateArgs, 'label' | 'type'>
  >;
  payoutRejectBulk?: Resolver<
    ResolversTypes['PayoutRejectBulkResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationPayoutRejectBulkArgs, 'ids'>
  >;
  pointOfSalePaymentCreate?: Resolver<
    ResolversTypes['PointOfSalePaymentCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<
      MutationPointOfSalePaymentCreateArgs,
      'amount' | 'application' | 'method' | 'orderId'
    >
  >;
  pointOfSalePaymentUpdate?: Resolver<
    ResolversTypes['PointOfSalePaymentUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationPointOfSalePaymentUpdateArgs, 'amount' | 'transaction' | 'url'>
  >;
  qrCodeCreate?: Resolver<
    ResolversTypes['QRCodeCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationQrCodeCreateArgs, 'type' | 'usage'>
  >;
  refreshAccessToken?: Resolver<
    ResolversTypes['RefreshAccessToken'],
    ParentType,
    ContextType,
    RequireFields<MutationRefreshAccessTokenArgs, 'clientId' | 'merchantId' | 'refreshToken'>
  >;
  registerBusiness?: Resolver<
    ResolversTypes['RegisterBusinessResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationRegisterBusinessArgs, 'name'>
  >;
  registerEmail?: Resolver<
    ResolversTypes['RegisterEmail'],
    ParentType,
    ContextType,
    RequireFields<
      MutationRegisterEmailArgs,
      'confirmPassword' | 'email' | 'password' | 'verificationMethod'
    >
  >;
  registerEmailVerify?: Resolver<
    ResolversTypes['RegisterEmailVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationRegisterEmailVerifyArgs, 'otp' | 'token'>
  >;
  registerFCMToken?: Resolver<
    ResolversTypes['RegisterFCMTokenResponse'],
    ParentType,
    ContextType,
    RequireFields<
      MutationRegisterFcmTokenArgs,
      'fcmToken' | 'platform' | 'productType' | 'tokenIdentifier'
    >
  >;
  registerMerchant?: Resolver<
    ResolversTypes['RegisterMerchantResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationRegisterMerchantArgs, 'contact'>
  >;
  registerMobileVerify?: Resolver<
    ResolversTypes['RegisterMobileVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationRegisterMobileVerifyArgs, 'captcha' | 'contact' | 'otp' | 'token'>
  >;
  registerOAuth?: Resolver<
    ResolversTypes['RegisterOAuth'],
    ParentType,
    ContextType,
    RequireFields<MutationRegisterOAuthArgs, 'email' | 'idToken' | 'platform' | 'provider'>
  >;
  rejectPayout?: Resolver<
    ResolversTypes['RejectPayoutResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationRejectPayoutArgs, 'id'>
  >;
  rejectPayoutBatch?: Resolver<
    ResolversTypes['RejectPayoutBatchResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationRejectPayoutBatchArgs, 'batchIds'>
  >;
  resendEmailOtp?: Resolver<
    ResolversTypes['ResendEmailOtp'],
    ParentType,
    ContextType,
    RequireFields<MutationResendEmailOtpArgs, 'token'>
  >;
  resendTwoFactorLoginOtp?: Resolver<
    ResolversTypes['ResendTwoFactorLoginOtpResponse'],
    ParentType,
    ContextType,
    Partial<MutationResendTwoFactorLoginOtpArgs>
  >;
  resetPasswordEmail?: Resolver<
    ResolversTypes['ResetPasswordEmail'],
    ParentType,
    ContextType,
    RequireFields<MutationResetPasswordEmailArgs, 'email'>
  >;
  sendApprovePayoutBatchOtp?: Resolver<
    ResolversTypes['SendApprovePayoutBatchOtp'],
    ParentType,
    ContextType,
    RequireFields<
      MutationSendApprovePayoutBatchOtpArgs,
      'bankingAccountNumber' | 'totalAmount' | 'totalCount'
    >
  >;
  sendApprovePayoutOtp?: Resolver<
    ResolversTypes['SendApprovePayoutOtp'],
    ParentType,
    ContextType,
    RequireFields<MutationSendApprovePayoutOtpArgs, 'amount' | 'bankingAccountNumber' | 'payoutId'>
  >;
  sendCreatePayoutLinkOtp?: Resolver<
    ResolversTypes['SendCreatePayoutLinkOtp'],
    ParentType,
    ContextType,
    RequireFields<
      MutationSendCreatePayoutLinkOtpArgs,
      'amount' | 'bankingAccountNumber' | 'purpose'
    >
  >;
  sendCreatePayoutOtp?: Resolver<
    ResolversTypes['SendCreatePayoutOtp'],
    ParentType,
    ContextType,
    RequireFields<
      MutationSendCreatePayoutOtpArgs,
      'amount' | 'bankingAccountNumber' | 'fundAccountId' | 'purpose'
    >
  >;
  sendIciciPayoutOtp?: Resolver<
    ResolversTypes['SendIciciPayoutOtpResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationSendIciciPayoutOtpArgs, 'payoutId'>
  >;
  sendPayoutApproveBulkOtp?: Resolver<
    ResolversTypes['SendPayoutApproveBulkOtp'],
    ParentType,
    ContextType,
    RequireFields<MutationSendPayoutApproveBulkOtpArgs, 'amount' | 'bankingAccountNumber' | 'count'>
  >;
  sendPayoutCompositeOtp?: Resolver<
    ResolversTypes['SendPayoutCompositeOtp'],
    ParentType,
    ContextType,
    RequireFields<MutationSendPayoutCompositeOtpArgs, 'amount' | 'bankingAccountNumber' | 'vpa'>
  >;
  smsNotificationToggle?: Resolver<
    ResolversTypes['SmsNotificationToggle'],
    ParentType,
    ContextType,
    RequireFields<MutationSmsNotificationToggleArgs, 'toggleValue'>
  >;
  twoFactorAddMobileOtp?: Resolver<
    ResolversTypes['TwoFactorAddMobileOtpResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationTwoFactorAddMobileOtpArgs, 'phone' | 'token'>
  >;
  twoFactorAddMobileOtpVerify?: Resolver<
    ResolversTypes['TwoFactorAddMobileOtpVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationTwoFactorAddMobileOtpVerifyArgs, 'otp' | 'phone'>
  >;
  twoFactorAuthUpdate?: Resolver<
    ResolversTypes['TwoFactorAuthUpdateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationTwoFactorAuthUpdateArgs, 'isTwoFactorEnabled'>
  >;
  twoFactorEmailOtpVerify?: Resolver<
    ResolversTypes['TwoFactorEmailOtpVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationTwoFactorEmailOtpVerifyArgs, 'action' | 'otp' | 'token'>
  >;
  twoFactorOtp?: Resolver<
    ResolversTypes['TwoFactorOtpResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationTwoFactorOtpArgs, 'action' | 'medium'>
  >;
  twoFactorPasswordCreate?: Resolver<
    ResolversTypes['TwoFactorPasswordCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationTwoFactorPasswordCreateArgs, 'confirmPassword' | 'password'>
  >;
  twoFactorUnverifiedMobileVerify?: Resolver<
    ResolversTypes['TwoFactorUnverifiedMobileVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationTwoFactorUnverifiedMobileVerifyArgs, 'otp' | 'token'>
  >;
  updateMerchantConsent?: Resolver<
    Maybe<ResolversTypes['UpdateMerchantConsentResponse']>,
    ParentType,
    ContextType,
    RequireFields<MutationUpdateMerchantConsentArgs, 'partnerId'>
  >;
  userContactDetailsUpdate?: Resolver<
    ResolversTypes['RegisterBusinessResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationUserContactDetailsUpdateArgs, 'name'>
  >;
  userDeviceAnalyticsUpdate?: Resolver<
    Maybe<ResolversTypes['UserDeviceAnalyticsResponse']>,
    ParentType,
    ContextType,
    RequireFields<MutationUserDeviceAnalyticsUpdateArgs, 'analyticsData'>
  >;
  userLogout?: Resolver<ResolversTypes['UserLogout'], ParentType, ContextType>;
  userLogoutOAuth?: Resolver<
    ResolversTypes['UserLogout'],
    ParentType,
    ContextType,
    RequireFields<MutationUserLogoutOAuthArgs, 'accessToken' | 'clientId'>
  >;
  userOtp?: Resolver<ResolversTypes['userOtpResponse'], ParentType, ContextType>;
  userOtpVerify?: Resolver<
    ResolversTypes['UserOtpVerifyResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationUserOtpVerifyArgs, 'otp'>
  >;
  vendorPaymentCancel?: Resolver<
    ResolversTypes['VendorPaymentCancelResponse'],
    ParentType,
    ContextType,
    RequireFields<MutationVendorPaymentCancelArgs, 'id'>
  >;
  vendorPaymentPayoutCreate?: Resolver<
    ResolversTypes['VendorPaymentPayoutCreateResponse'],
    ParentType,
    ContextType,
    RequireFields<
      MutationVendorPaymentPayoutCreateArgs,
      | 'amount'
      | 'bankingAccountNumber'
      | 'fundAccountId'
      | 'mode'
      | 'otp'
      | 'purpose'
      | 'queueOnLowBalance'
      | 'token'
      | 'vendorPaymentId'
    >
  >;
  whatsappNotificationToggle?: Resolver<
    ResolversTypes['WhatsappNotificationToggle'],
    ParentType,
    ContextType,
    RequireFields<MutationWhatsappNotificationToggleArgs, 'source' | 'toggleValue'>
  >;
};

export type MutationResponseInterfaceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['MutationResponseInterface'] = ResolversParentTypes['MutationResponseInterface'],
> = {
  __resolveType: TypeResolveFn<
    | 'AadhaarCaptchaVerifyResponse'
    | 'AadhaarOtpVerifyResponse'
    | 'ApproveIciciPayoutResponse'
    | 'ApprovePayoutResponse'
    | 'AuthUser'
    | 'CouponApplyResponse'
    | 'CouponValidateResponse'
    | 'DeregisterFCMTokenResponse'
    | 'LoginOtpError'
    | 'LoginOtpSuccess'
    | 'MerchantActivationResponse'
    | 'MerchantApiKeyCreateResponse'
    | 'MerchantApiKeyRegenerateResponse'
    | 'MerchantApiKeysCreateFailure'
    | 'MerchantApiKeysCreateSuccess'
    | 'MerchantBankAccountDocumentUploadSuccessResponse'
    | 'MerchantBankAccountUpdateFailureResponse'
    | 'MerchantBankAccountUpdateSuccessResponse'
    | 'MerchantBusinessAppDetailsResponse'
    | 'MerchantBusinessWebsiteDetailsResponse'
    | 'MerchantClarificationDetailsSubmitResponse'
    | 'MerchantClarificationDetailsUpdateResponse'
    | 'MerchantConfigUpdateResponse'
    | 'MerchantConsentFailure'
    | 'MerchantContactCreateResponse'
    | 'MerchantContactEmailOtpSendFailureResponse'
    | 'MerchantContactEmailOtpSendSuccessResponse'
    | 'MerchantContactFundAccountCreateResponse'
    | 'MerchantContactUpdateResponse'
    | 'MerchantDocumentUploadSuccessResponse'
    | 'MerchantGstinUpdateAsyncFlowSuccessResponse'
    | 'MerchantGstinUpdateInSyncFlowResponse'
    | 'MerchantGstinUpdateInSyncWorkFlowCreatedResponse'
    | 'MerchantNcEligibilityResponse'
    | 'MerchantPaymentHandleCreateSuccessResponse'
    | 'MerchantPaymentHandleUpdateSuccessResponse'
    | 'MerchantPolicyPublishSuccessResponse'
    | 'MerchantPolicyWizardV2EligibilityResponse'
    | 'MerchantSwitchResponse'
    | 'MerchantWebsiteDocumentDeleteSuccessResponse'
    | 'MerchantWebsiteDocumentUploadSuccessResponse'
    | 'MerchantWebsitePublishSuccessResponse'
    | 'MerchantWorkflowClarificationSubmitSuccessResponse'
    | 'NotificationEmailUpdateFailureResponse'
    | 'NotificationEmailUpdateSuccessResponse'
    | 'NotificationWhatsAppOptIn'
    | 'OauthTokenAppleWatchOtp'
    | 'OauthTokenAppleWatchResponseError'
    | 'OnboardingPaymentOrderCreateFailureResponse'
    | 'OnboardingPaymentOrderCreateSuccessResponse'
    | 'OnboardingPaymentOrderVerifyResponse'
    | 'OrderCreateFailureResponse'
    | 'OrderCreateSuccessResponse'
    | 'PaymentCaptureResponse'
    | 'PaymentLinkCancelResponse'
    | 'PaymentLinkCreateResponse'
    | 'PaymentLinkNotifyResponse'
    | 'PaymentRefundResponse'
    | 'PaymentsNewLaunchProductViewUpdate'
    | 'PaymentsProductFtuxUpdateResponse'
    | 'PayoutCompositeCreateResponse'
    | 'PayoutCreateIciciResponse'
    | 'PayoutCreateResponse'
    | 'PayoutLinkCreateResponse'
    | 'PayoutPurposeCreateResponse'
    | 'PointOfSalePaymentCreateFailureResponse'
    | 'PointOfSalePaymentCreateSuccessResponse'
    | 'PointOfSalePaymentUpdateFailureResponse'
    | 'PointOfSalePaymentUpdateSuccessResponse'
    | 'QRCodeCreateFailureResponse'
    | 'QRCodeCreateSuccessResponse'
    | 'RegisterBusinessResponse'
    | 'RegisterEmailError'
    | 'RegisterEmailSuccess'
    | 'RegisterFCMTokenResponse'
    | 'RegisterMerchantResponseFailure'
    | 'RegisterMerchantResponseSuccess'
    | 'RegisterMobileVerifyResponseFailure'
    | 'RegisterMobileVerifyResponseSuccess'
    | 'RejectPayoutResponse'
    | 'ResendEmailOtp'
    | 'ResendTwoFactorLoginOtpResponse'
    | 'SendApprovePayoutBatchOtp'
    | 'SendApprovePayoutOtp'
    | 'SendCreatePayoutLinkOtp'
    | 'SendCreatePayoutOtp'
    | 'SendIciciPayoutOtpResponse'
    | 'SendPayoutApproveBulkOtp'
    | 'SendPayoutCompositeOtp'
    | 'SmsNotificationToggle'
    | 'TwoFactorAddMobileOtpErrorResponse'
    | 'TwoFactorAddMobileOtpSuccessResponse'
    | 'TwoFactorAddMobileOtpVerifyErrorResponse'
    | 'TwoFactorAddMobileOtpVerifySuccessResponse'
    | 'TwoFactorEmailOtpVerifyFailureResponse'
    | 'TwoFactorEmailOtpVerifySuccessResponse'
    | 'TwoFactorOtpFailureResponse'
    | 'TwoFactorOtpSuccessResponse'
    | 'TwoFactorPasswordCreateErrorResponse'
    | 'TwoFactorPasswordCreateSuccessResponse'
    | 'TwoFactorUnverifiedMobileVerifyResponse'
    | 'UpdateMerchantConsentResponse'
    | 'UserContactDetailsUpdateResponse'
    | 'UserDeviceAnalyticsResponse'
    | 'UserOtpVerifyResponse'
    | 'VendorPaymentCancelResponse'
    | 'VendorPaymentPayoutCreateResponse'
    | 'WhatsappNotificationToggle'
    | 'merchantConfigurationUpdateResponse',
    ParentType,
    ContextType
  >;
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
};

export interface NonNegativeIntScalarConfig
  extends GraphQLScalarTypeConfig<ResolversTypes['NonNegativeInt'], any> {
  name: 'NonNegativeInt';
}

export type NotificationEmailUpdateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['NotificationEmailUpdateFailureResponse'] = ResolversParentTypes['NotificationEmailUpdateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type NotificationEmailUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['NotificationEmailUpdateResponse'] = ResolversParentTypes['NotificationEmailUpdateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'NotificationEmailUpdateFailureResponse' | 'NotificationEmailUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type NotificationEmailUpdateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['NotificationEmailUpdateSuccessResponse'] = ResolversParentTypes['NotificationEmailUpdateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  transactionReportEmail?: Resolver<
    Array<Maybe<ResolversTypes['EmailAddress']>>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type NotificationWhatsAppOptInResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['NotificationWhatsAppOptIn'] = ResolversParentTypes['NotificationWhatsAppOptIn'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OauthTokenAppleWatchOtpResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OauthTokenAppleWatchOtp'] = ResolversParentTypes['OauthTokenAppleWatchOtp'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OauthTokenAppleWatchResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OauthTokenAppleWatchResponse'] = ResolversParentTypes['OauthTokenAppleWatchResponse'],
> = {
  __resolveType: TypeResolveFn<
    'OauthTokenAppleWatchResponseError' | 'OauthTokenAppleWatchResponseSuccess',
    ParentType,
    ContextType
  >;
};

export type OauthTokenAppleWatchResponseErrorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OauthTokenAppleWatchResponseError'] = ResolversParentTypes['OauthTokenAppleWatchResponseError'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OauthTokenAppleWatchResponseSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OauthTokenAppleWatchResponseSuccess'] = ResolversParentTypes['OauthTokenAppleWatchResponseSuccess'],
> = {
  accessToken?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  accountId?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  expiresIn?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  publicToken?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  tokenType?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OnboardingPaymentOrderCreateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OnboardingPaymentOrderCreateFailureResponse'] = ResolversParentTypes['OnboardingPaymentOrderCreateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OnboardingPaymentOrderCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OnboardingPaymentOrderCreateResponse'] = ResolversParentTypes['OnboardingPaymentOrderCreateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'OnboardingPaymentOrderCreateFailureResponse' | 'OnboardingPaymentOrderCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type OnboardingPaymentOrderCreateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OnboardingPaymentOrderCreateSuccessResponse'] = ResolversParentTypes['OnboardingPaymentOrderCreateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  order?: Resolver<ResolversTypes['Order'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OnboardingPaymentOrderVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OnboardingPaymentOrderVerifyResponse'] = ResolversParentTypes['OnboardingPaymentOrderVerifyResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OnboardingWidgetResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OnboardingWidget'] = ResolversParentTypes['OnboardingWidget'],
> = {
  title?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['PaymentsWidgetTypeEnum'], ParentType, ContextType>;
  variant?: Resolver<ResolversTypes['WidgetVariantEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OrderResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Order'] = ResolversParentTypes['Order'],
> = {
  amount?: Resolver<ResolversTypes['OrderAmount'], ParentType, ContextType>;
  dates?: Resolver<ResolversTypes['OrderDate'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  paymentAttempts?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  receipt?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['OrderStatusEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OrderAmountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OrderAmount'] = ResolversParentTypes['OrderAmount'],
> = {
  due?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  generated?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  paid?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OrderCreateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OrderCreateFailureResponse'] = ResolversParentTypes['OrderCreateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OrderCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OrderCreateResponse'] = ResolversParentTypes['OrderCreateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'OrderCreateFailureResponse' | 'OrderCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type OrderCreateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OrderCreateSuccessResponse'] = ResolversParentTypes['OrderCreateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  order?: Resolver<ResolversTypes['Order'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OrderDateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OrderDate'] = ResolversParentTypes['OrderDate'],
> = {
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OrganisationResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Organisation'] = ResolversParentTypes['Organisation'],
> = {
  allowedEmailDomains?: Resolver<Array<ResolversTypes['String']>, ParentType, ContextType>;
  code?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  domain?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  email?: Resolver<ResolversTypes['OrganisationEmail'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  logo?: Resolver<Maybe<ResolversTypes['OrganisationLogo']>, ParentType, ContextType>;
  name?: Resolver<ResolversTypes['OrganisationName'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OrganisationEmailResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OrganisationEmail'] = ResolversParentTypes['OrganisationEmail'],
> = {
  from?: Resolver<ResolversTypes['EmailAddress'], ParentType, ContextType>;
  to?: Resolver<ResolversTypes['EmailAddress'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OrganisationLogoResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OrganisationLogo'] = ResolversParentTypes['OrganisationLogo'],
> = {
  header?: Resolver<Maybe<ResolversTypes['Image']>, ParentType, ContextType>;
  invoice?: Resolver<Maybe<ResolversTypes['Image']>, ParentType, ContextType>;
  login?: Resolver<Maybe<ResolversTypes['Image']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OrganisationNameResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OrganisationName'] = ResolversParentTypes['OrganisationName'],
> = {
  display?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  registered?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type OverViewResponseTypeResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['OverViewResponseType'] = ResolversParentTypes['OverViewResponseType'],
> = {
  count?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  reason?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PpTrackingSettingsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PPTrackingSettings'] = ResolversParentTypes['PPTrackingSettings'],
> = {
  ppFbEventAddToCartEnabled?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  ppFbEventInitiatePaymentEnabled?: Resolver<
    Maybe<ResolversTypes['String']>,
    ParentType,
    ContextType
  >;
  ppFbEventPaymentCompleteEnabled?: Resolver<
    Maybe<ResolversTypes['String']>,
    ParentType,
    ContextType
  >;
  ppFbPixelTrackingId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  ppGaPixelTrackingId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PageAcquirerDataResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PageAcquirerData'] = ResolversParentTypes['PageAcquirerData'],
> = {
  transactionId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PageItemResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PageItem'] = ResolversParentTypes['PageItem'],
> = {
  active?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  amount?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  taxDetails?: Resolver<ResolversTypes['PageItemTaxDetails'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PageItemTaxDetailsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PageItemTaxDetails'] = ResolversParentTypes['PageItemTaxDetails'],
> = {
  sacCode?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  taxGroupId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  taxId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  taxInclusive?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  taxRate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaginationResponseInterfaceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaginationResponseInterface'] = ResolversParentTypes['PaginationResponseInterface'],
> = {
  __resolveType: TypeResolveFn<
    | 'InvoicesResponse'
    | 'MerchantContactFundAccountsResponse'
    | 'MerchantContactsResponse'
    | 'MerchantVirtualAccountsResponse'
    | 'PaymentLinksResponse'
    | 'PaymentPageTransactionResponse'
    | 'PaymentPagesResponse'
    | 'PaymentsResponse'
    | 'PayoutBatchesResponse'
    | 'PayoutLinksResponse'
    | 'PayoutsResponse'
    | 'QRCodesResponse'
    | 'RefundsResponse'
    | 'SettlementsResponse'
    | 'TransactionsResponse'
    | 'VendorPaymentsResponse',
    ParentType,
    ContextType
  >;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
};

export type PartnerConfigFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PartnerConfigFailure'] = ResolversParentTypes['PartnerConfigFailure'],
> = {
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PartnerConfigResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PartnerConfigResponse'] = ResolversParentTypes['PartnerConfigResponse'],
> = {
  __resolveType: TypeResolveFn<
    'PartnerConfigFailure' | 'PartnerConfigSuccess',
    ParentType,
    ContextType
  >;
};

export type PartnerConfigSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PartnerConfigSuccess'] = ResolversParentTypes['PartnerConfigSuccess'],
> = {
  brandColor?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  logoUrl?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  textColor?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PartnerWebhookSettingsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PartnerWebhookSettings'] = ResolversParentTypes['PartnerWebhookSettings'],
> = {
  partnerShiprocket?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Payment'] = ResolversParentTypes['Payment'],
> = {
  amount?: Resolver<ResolversTypes['PaymentAmount'], ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  customer?: Resolver<Maybe<ResolversTypes['Customer']>, ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  error?: Resolver<Maybe<ResolversTypes['PaymentError']>, ParentType, ContextType>;
  feeBearer?: Resolver<Maybe<ResolversTypes['FeeBearerEnum']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  isCaptured?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isInternational?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  method?: Resolver<Maybe<ResolversTypes['PaymentMethod']>, ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  refunds?: Resolver<Maybe<Array<ResolversTypes['PaymentRefund']>>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['PaymentStatusEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentAggregationSummaryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentAggregationSummary'] = ResolversParentTypes['PaymentAggregationSummary'],
> = {
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  sum?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentAmountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentAmount'] = ResolversParentTypes['PaymentAmount'],
> = {
  charged?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  converted?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  fee?: Resolver<Maybe<ResolversTypes['Float']>, ParentType, ContextType>;
  tax?: Resolver<Maybe<ResolversTypes['Float']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentAnalyticsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentAnalytics'] = ResolversParentTypes['PaymentAnalytics'],
> = {
  bank?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  createdAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  device?: Resolver<
    Maybe<ResolversTypes['PaymentAnalyticsFilterByDeviceEnum']>,
    ParentType,
    ContextType
  >;
  issuer?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  method?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  network?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  os?: Resolver<Maybe<ResolversTypes['PaymentAnalyticsFilterByOsEnum']>, ParentType, ContextType>;
  platform?: Resolver<
    Maybe<ResolversTypes['PaymentAnalyticsFilterBySdkEnum']>,
    ParentType,
    ContextType
  >;
  type?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  value?: Resolver<ResolversTypes['BigInt'], ParentType, ContextType>;
  wallet?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentAnalyticsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentAnalyticsResponse'] = ResolversParentTypes['PaymentAnalyticsResponse'],
> = {
  aggregatedBy?: Resolver<
    ResolversTypes['PaymentAnalyticsAggregateByEnum'],
    ParentType,
    ContextType
  >;
  analytics?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['PaymentAnalytics']>>>,
    ParentType,
    ContextType
  >;
  interval?: Resolver<
    Maybe<ResolversTypes['PaymentAnalyticsIntervalEnum']>,
    ParentType,
    ContextType
  >;
  updatedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentAnalyticsWidgetResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentAnalyticsWidget'] = ResolversParentTypes['PaymentAnalyticsWidget'],
> = {
  title?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['PaymentsWidgetTypeEnum'], ParentType, ContextType>;
  variant?: Resolver<ResolversTypes['WidgetVariantEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentCaptureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentCaptureResponse'] = ResolversParentTypes['PaymentCaptureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentDetailsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentDetails'] = ResolversParentTypes['PaymentDetails'],
> = {
  bank?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  cardId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  fee?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  international?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  invoiceId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  method?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  refundStatus?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  tax?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  vpa?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  wallet?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentEmiDetailsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentEmiDetails'] = ResolversParentTypes['PaymentEmiDetails'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  duration?: Resolver<ResolversTypes['Float'], ParentType, ContextType>;
  rate?: Resolver<ResolversTypes['Float'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentErrorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentError'] = ResolversParentTypes['PaymentError'],
> = {
  code?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentHandleWidgetResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentHandleWidget'] = ResolversParentTypes['PaymentHandleWidget'],
> = {
  description?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  paymentHandle?: Resolver<ResolversTypes['MerchantPaymentHandle'], ParentType, ContextType>;
  title?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['PaymentsWidgetTypeEnum'], ParentType, ContextType>;
  variant?: Resolver<ResolversTypes['WidgetVariantEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentInstantRefundEligibilityAmountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentInstantRefundEligibilityAmount'] = ResolversParentTypes['PaymentInstantRefundEligibilityAmount'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  fee?: Resolver<Maybe<ResolversTypes['Float']>, ParentType, ContextType>;
  tax?: Resolver<Maybe<ResolversTypes['Float']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentInstantRefundEligibilityResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentInstantRefundEligibilityResponse'] = ResolversParentTypes['PaymentInstantRefundEligibilityResponse'],
> = {
  isAllowed?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  messages?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  option?: Resolver<
    ResolversTypes['PaymentInstantRefundEligibilityOptionEnum'],
    ParentType,
    ContextType
  >;
  refund?: Resolver<
    Maybe<ResolversTypes['PaymentInstantRefundEligibilityAmount']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentLinkResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentLink'] = ResolversParentTypes['PaymentLink'],
> = {
  amount?: Resolver<ResolversTypes['PaymentLinkAmount'], ParentType, ContextType>;
  customer?: Resolver<Maybe<ResolversTypes['Customer']>, ParentType, ContextType>;
  dates?: Resolver<ResolversTypes['PaymentLinkDate'], ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  isPartiallyPayable?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  notifyBy?: Resolver<Maybe<ResolversTypes['PaymentLinkNotifyBy']>, ParentType, ContextType>;
  orderId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  payments?: Resolver<Maybe<Array<ResolversTypes['Payment']>>, ParentType, ContextType>;
  referenceId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  reminder?: Resolver<Maybe<ResolversTypes['PaymentLinkReminder']>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['PaymentLinkStatusEnum'], ParentType, ContextType>;
  url?: Resolver<ResolversTypes['URL'], ParentType, ContextType>;
  user?: Resolver<Maybe<ResolversTypes['User']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentLinkAmountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentLinkAmount'] = ResolversParentTypes['PaymentLinkAmount'],
> = {
  due?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  firstMinimumPartialAmount?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  generated?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  paid?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentLinkCancelResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentLinkCancelResponse'] = ResolversParentTypes['PaymentLinkCancelResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentLinkCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentLinkCreateResponse'] = ResolversParentTypes['PaymentLinkCreateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  paymentLink?: Resolver<ResolversTypes['PaymentLink'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentLinkDateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentLinkDate'] = ResolversParentTypes['PaymentLinkDate'],
> = {
  cancelledAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  deletedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  expireBy?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  expiredAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  updatedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentLinkNotifyByResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentLinkNotifyBy'] = ResolversParentTypes['PaymentLinkNotifyBy'],
> = {
  email?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  sms?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentLinkNotifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentLinkNotifyResponse'] = ResolversParentTypes['PaymentLinkNotifyResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentLinkReminderResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentLinkReminder'] = ResolversParentTypes['PaymentLinkReminder'],
> = {
  isEnabled?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  status?: Resolver<
    Maybe<ResolversTypes['PaymentLinkReminderStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentLinksResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentLinksResponse'] = ResolversParentTypes['PaymentLinksResponse'],
> = {
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  paymentLinks?: Resolver<Array<ResolversTypes['PaymentLink']>, ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethod'] = ResolversParentTypes['PaymentMethod'],
> = {
  __resolveType: TypeResolveFn<
    | 'PaymentMethodApp'
    | 'PaymentMethodBankTransfer'
    | 'PaymentMethodCard'
    | 'PaymentMethodCardlessEmi'
    | 'PaymentMethodEmandate'
    | 'PaymentMethodEmi'
    | 'PaymentMethodNetBanking'
    | 'PaymentMethodPayLater'
    | 'PaymentMethodUPITransfer'
    | 'PaymentMethodWallet',
    ParentType,
    ContextType
  >;
};

export type PaymentMethodAppResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodApp'] = ResolversParentTypes['PaymentMethodApp'],
> = {
  provider?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodBankTransferResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodBankTransfer'] = ResolversParentTypes['PaymentMethodBankTransfer'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  bankReference?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  mode?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  payerBankAccount?: Resolver<
    Maybe<ResolversTypes['PaymentPayerBankAccount']>,
    ParentType,
    ContextType
  >;
  virtualAccount?: Resolver<ResolversTypes['PaymentVirtualAccount'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodCardResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodCard'] = ResolversParentTypes['PaymentMethodCard'],
> = {
  category?: Resolver<
    Maybe<ResolversTypes['PaymentMethodCardCategoryEnum']>,
    ParentType,
    ContextType
  >;
  expiry?: Resolver<ResolversTypes['PaymentMethodCardExpiry'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  isEmi?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  isInternational?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  issuer?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  lastFourDigits?: Resolver<ResolversTypes['Int'], ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  network?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['PaymentMethodCardType'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodCardExpiryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodCardExpiry'] = ResolversParentTypes['PaymentMethodCardExpiry'],
> = {
  month?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  year?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodCardlessEmiResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodCardlessEmi'] = ResolversParentTypes['PaymentMethodCardlessEmi'],
> = {
  cardlessEmi?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodEmandateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodEmandate'] = ResolversParentTypes['PaymentMethodEmandate'],
> = {
  emandate?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodEmiResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodEmi'] = ResolversParentTypes['PaymentMethodEmi'],
> = {
  card?: Resolver<ResolversTypes['PaymentMethodCard'], ParentType, ContextType>;
  emi?: Resolver<ResolversTypes['PaymentEmiDetails'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodNetBankingResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodNetBanking'] = ResolversParentTypes['PaymentMethodNetBanking'],
> = {
  bankName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodPayLaterResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodPayLater'] = ResolversParentTypes['PaymentMethodPayLater'],
> = {
  payLater?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodUpiTransferResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodUPITransfer'] = ResolversParentTypes['PaymentMethodUPITransfer'],
> = {
  vpa?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentMethodWalletResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentMethodWallet'] = ResolversParentTypes['PaymentMethodWallet'],
> = {
  wallet?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentOverviewResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentOverviewResponse'] = ResolversParentTypes['PaymentOverviewResponse'],
> = {
  paymentCollected?: Resolver<
    Maybe<ResolversTypes['PaymentAggregationSummary']>,
    ParentType,
    ContextType
  >;
  refundFailed?: Resolver<
    Maybe<ResolversTypes['PaymentAggregationSummary']>,
    ParentType,
    ContextType
  >;
  refundProcessed?: Resolver<
    Maybe<ResolversTypes['PaymentAggregationSummary']>,
    ParentType,
    ContextType
  >;
  refundProcessing?: Resolver<
    Maybe<ResolversTypes['PaymentAggregationSummary']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentPageResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentPage'] = ResolversParentTypes['PaymentPage'],
> = {
  amount?: Resolver<ResolversTypes['PaymentPageAmount'], ParentType, ContextType>;
  dates?: Resolver<ResolversTypes['PaymentPageDate'], ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  orderId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  paymentPagesItems?: Resolver<Array<ResolversTypes['PaymentPageItem']>, ParentType, ContextType>;
  settings?: Resolver<Maybe<ResolversTypes['PaymentPageSettings']>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['PaymentPageStatusEnum'], ParentType, ContextType>;
  support?: Resolver<Maybe<ResolversTypes['PaymentPageSupportDetails']>, ParentType, ContextType>;
  terms?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  timesPaid?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  timesPayable?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  title?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  url?: Resolver<ResolversTypes['URL'], ParentType, ContextType>;
  user?: Resolver<Maybe<ResolversTypes['User']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentPageAmountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentPageAmount'] = ResolversParentTypes['PaymentPageAmount'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalAmountPaid?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentPageDateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentPageDate'] = ResolversParentTypes['PaymentPageDate'],
> = {
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  expireBy?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  updatedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentPageItemResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentPageItem'] = ResolversParentTypes['PaymentPageItem'],
> = {
  entity?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  hsnCode?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  imageUrl?: Resolver<Maybe<ResolversTypes['URL']>, ParentType, ContextType>;
  item?: Resolver<Maybe<ResolversTypes['PageItem']>, ParentType, ContextType>;
  mandatory?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  minPurchase?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  paymentLinkId?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  planId?: Resolver<Maybe<ResolversTypes['ID']>, ParentType, ContextType>;
  quantitySold?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  stock?: Resolver<Maybe<ResolversTypes['Int']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentPageSettingsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentPageSettings'] = ResolversParentTypes['PaymentPageSettings'],
> = {
  allowSocialShare?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  checkoutOptions?: Resolver<Maybe<ResolversTypes['CheckoutOptions']>, ParentType, ContextType>;
  enable80GDetails?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  enableCustomSerialNumber?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  enableReceipt?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  goalTracker?: Resolver<Maybe<ResolversTypes['GoalTrackerSettings']>, ParentType, ContextType>;
  partnerWebhookSettings?: Resolver<
    Maybe<ResolversTypes['PartnerWebhookSettings']>,
    ParentType,
    ContextType
  >;
  paymentButtonLabel?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  paymentSuccessMessage?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  paymentSuccessRedirectURL?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  selectedUdfField?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  theme?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  trackingSettings?: Resolver<Maybe<ResolversTypes['PPTrackingSettings']>, ParentType, ContextType>;
  udfSchema?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  version?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentPageSupportDetailsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentPageSupportDetails'] = ResolversParentTypes['PaymentPageSupportDetails'],
> = {
  email?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  phone?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentPageTransactionResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentPageTransaction'] = ResolversParentTypes['PaymentPageTransaction'],
> = {
  acquirerData?: Resolver<Maybe<ResolversTypes['PageAcquirerData']>, ParentType, ContextType>;
  amount?: Resolver<Maybe<ResolversTypes['TransactionAmount']>, ParentType, ContextType>;
  createdAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  entity?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  error?: Resolver<Maybe<ResolversTypes['TransactionError']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  paymentDetails?: Resolver<Maybe<ResolversTypes['PaymentDetails']>, ParentType, ContextType>;
  status?: Resolver<Maybe<ResolversTypes['TransactionStatus']>, ParentType, ContextType>;
  userDetails?: Resolver<Maybe<ResolversTypes['UserContactDetails']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentPageTransactionResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentPageTransactionResponse'] = ResolversParentTypes['PaymentPageTransactionResponse'],
> = {
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  paymentPagesTransactions?: Resolver<
    Maybe<Array<ResolversTypes['PaymentPageTransaction']>>,
    ParentType,
    ContextType
  >;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentPagesResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentPagesResponse'] = ResolversParentTypes['PaymentPagesResponse'],
> = {
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  paymentPages?: Resolver<Array<ResolversTypes['PaymentPage']>, ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentPayerBankAccountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentPayerBankAccount'] = ResolversParentTypes['PaymentPayerBankAccount'],
> = {
  accountNumber?: Resolver<ResolversTypes['BigInt'], ParentType, ContextType>;
  bankName?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  ifsc?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentRefundResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentRefund'] = ResolversParentTypes['PaymentRefund'],
> = {
  acquirerData?: Resolver<Maybe<ResolversTypes['AcquirerData']>, ParentType, ContextType>;
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  batchId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  entity?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  paymentId?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  processedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  speed?: Resolver<Maybe<ResolversTypes['PaymentRefundSpeed']>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['PaymentRefundStatusEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentRefundResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentRefundResponse'] = ResolversParentTypes['PaymentRefundResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentRefundSpeedResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentRefundSpeed'] = ResolversParentTypes['PaymentRefundSpeed'],
> = {
  processed?: Resolver<
    Maybe<ResolversTypes['PaymentRefundSpeedProcessedEnum']>,
    ParentType,
    ContextType
  >;
  requested?: Resolver<
    Maybe<ResolversTypes['PaymentRefundSpeedRequestedEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentSummaryResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentSummaryResponse'] = ResolversParentTypes['PaymentSummaryResponse'],
> = {
  data?: Resolver<Maybe<Array<ResolversTypes['AggregationResultType']>>, ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['PositiveInt']>, ParentType, ContextType>;
  updatedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentTermResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentTerm'] = ResolversParentTypes['PaymentTerm'],
> = {
  days?: Resolver<Maybe<ResolversTypes['PositiveInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentVirtualAccountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentVirtualAccount'] = ResolversParentTypes['PaymentVirtualAccount'],
> = {
  amount?: Resolver<ResolversTypes['PaymentVirtualAccountAmount'], ParentType, ContextType>;
  customer?: Resolver<Maybe<ResolversTypes['Customer']>, ParentType, ContextType>;
  dates?: Resolver<ResolversTypes['PaymentVirtualAccountDates'], ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  notes?: Resolver<Maybe<Array<Maybe<ResolversTypes['JSONObject']>>>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentVirtualAccountAmountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentVirtualAccountAmount'] = ResolversParentTypes['PaymentVirtualAccountAmount'],
> = {
  expected?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  paid?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentVirtualAccountDatesResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentVirtualAccountDates'] = ResolversParentTypes['PaymentVirtualAccountDates'],
> = {
  closeBy?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  closedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentsNewLaunchProductViewUpdateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentsNewLaunchProductViewUpdate'] = ResolversParentTypes['PaymentsNewLaunchProductViewUpdate'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentsProductFtuxUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentsProductFtuxUpdateResponse'] = ResolversParentTypes['PaymentsProductFtuxUpdateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentsResponse'] = ResolversParentTypes['PaymentsResponse'],
> = {
  hasMore?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  payments?: Resolver<Array<ResolversTypes['Payment']>, ParentType, ContextType>;
  total?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentsWidgetErrorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentsWidgetError'] = ResolversParentTypes['PaymentsWidgetError'],
> = {
  errorCode?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  errorDescription?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['PaymentsWidgetTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PaymentsWidgetsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PaymentsWidgets'] = ResolversParentTypes['PaymentsWidgets'],
> = {
  segment?: Resolver<ResolversTypes['PaymentsSegmentEnum'], ParentType, ContextType>;
  widgets?: Resolver<Array<ResolversTypes['Widget']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Payout'] = ResolversParentTypes['Payout'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  bankingAccount?: Resolver<
    Maybe<ResolversTypes['MerchantBankingAccount']>,
    ParentType,
    ContextType
  >;
  batchId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  cancelledBy?: Resolver<Maybe<ResolversTypes['User']>, ParentType, ContextType>;
  dates?: Resolver<Maybe<ResolversTypes['PayoutDate']>, ParentType, ContextType>;
  failureReason?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  fee?: Resolver<Maybe<ResolversTypes['PayoutFee']>, ParentType, ContextType>;
  fundAccount?: Resolver<
    Maybe<ResolversTypes['MerchantContactFundAccount']>,
    ParentType,
    ContextType
  >;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  internalStatus?: Resolver<
    Maybe<ResolversTypes['PayoutInternalStatusEnum']>,
    ParentType,
    ContextType
  >;
  isPendingOnMe?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  mode?: Resolver<ResolversTypes['PayoutModeEnum'], ParentType, ContextType>;
  narration?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  pendingReason?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  purpose?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  referenceId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  remark?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  sources?: Resolver<Array<ResolversTypes['PayoutSource']>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['PayoutStatusEnum'], ParentType, ContextType>;
  statusDetails?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  tax?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  transaction?: Resolver<Maybe<ResolversTypes['Transaction']>, ParentType, ContextType>;
  user?: Resolver<Maybe<ResolversTypes['User']>, ParentType, ContextType>;
  utr?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  workflow?: Resolver<Maybe<ResolversTypes['PayoutWorkflow']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutApproveBulkResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutApproveBulkResponse'] = ResolversParentTypes['PayoutApproveBulkResponse'],
> = {
  __resolveType: TypeResolveFn<
    'PayoutApproveBulkResponseFailure' | 'PayoutApproveBulkResponseSuccess',
    ParentType,
    ContextType
  >;
};

export type PayoutApproveBulkResponseFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutApproveBulkResponseFailure'] = ResolversParentTypes['PayoutApproveBulkResponseFailure'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  failedPayoutIds?: Resolver<Maybe<Array<ResolversTypes['ID']>>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutApproveBulkResponseSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutApproveBulkResponseSuccess'] = ResolversParentTypes['PayoutApproveBulkResponseSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutBatchResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutBatch'] = ResolversParentTypes['PayoutBatch'],
> = {
  bankingAccount?: Resolver<
    Maybe<ResolversTypes['MerchantBankingAccount']>,
    ParentType,
    ContextType
  >;
  batchType?: Resolver<ResolversTypes['PayoutBatchTypeEnum'], ParentType, ContextType>;
  createdBy?: Resolver<ResolversTypes['User'], ParentType, ContextType>;
  dates?: Resolver<ResolversTypes['PayoutBatchDates'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  isPendingOnMe?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  payoutPurpose?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  payoutsCount?: Resolver<
    Maybe<ResolversTypes['PayoutBatchPayoutsCount']>,
    ParentType,
    ContextType
  >;
  processedAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  processingBatchId?: Resolver<Maybe<ResolversTypes['ID']>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['PayoutBatchStatusEnum'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  validationBatchId?: Resolver<Maybe<ResolversTypes['ID']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutBatchDatesResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutBatchDates'] = ResolversParentTypes['PayoutBatchDates'],
> = {
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  failedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  pendingAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  processedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  processingAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  rejectedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  validatedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  validatingAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutBatchPayoutsCountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutBatchPayoutsCount'] = ResolversParentTypes['PayoutBatchPayoutsCount'],
> = {
  failure?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  processed?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  validated?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutBatchesResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutBatchesResponse'] = ResolversParentTypes['PayoutBatchesResponse'],
> = {
  hasMore?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  payoutBatches?: Resolver<Array<ResolversTypes['PayoutBatch']>, ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutCompositeCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutCompositeCreateResponse'] = ResolversParentTypes['PayoutCompositeCreateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  payout?: Resolver<Maybe<ResolversTypes['Payout']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutCreateIciciResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutCreateIciciResponse'] = ResolversParentTypes['PayoutCreateIciciResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  payoutId?: Resolver<Maybe<ResolversTypes['ID']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutCreateResponse'] = ResolversParentTypes['PayoutCreateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  payout?: Resolver<Maybe<ResolversTypes['Payout']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutDateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutDate'] = ResolversParentTypes['PayoutDate'],
> = {
  cancelledAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  failedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  initiatedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  pendingAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  processedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  queuedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  rejectedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  reversedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  scheduledAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  scheduledOn?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutFeeResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutFee'] = ResolversParentTypes['PayoutFee'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  type?: Resolver<Maybe<ResolversTypes['PayoutFeeEnum']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutLinkResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutLink'] = ResolversParentTypes['PayoutLink'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  attemptCount?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  dates?: Resolver<Maybe<ResolversTypes['PayoutLinkDate']>, ParentType, ContextType>;
  description?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  fundAccount?: Resolver<
    Maybe<ResolversTypes['MerchantContactFundAccount']>,
    ParentType,
    ContextType
  >;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  merchantContact?: Resolver<ResolversTypes['MerchantContact'], ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  payouts?: Resolver<
    Array<ResolversTypes['Payout']>,
    ParentType,
    ContextType,
    Partial<PayoutLinkPayoutsArgs>
  >;
  purpose?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  referenceId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  sentVia?: Resolver<ResolversTypes['PayoutLinkSentVia'], ParentType, ContextType>;
  shortUrl?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['PayoutLinkStatusEnum'], ParentType, ContextType>;
  user?: Resolver<Maybe<ResolversTypes['User']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutLinkCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutLinkCreateResponse'] = ResolversParentTypes['PayoutLinkCreateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  payoutLink?: Resolver<Maybe<ResolversTypes['PayoutLink']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutLinkDateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutLinkDate'] = ResolversParentTypes['PayoutLinkDate'],
> = {
  cancelledAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  expiredAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutLinkSentViaResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutLinkSentVia'] = ResolversParentTypes['PayoutLinkSentVia'],
> = {
  email?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  sms?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutLinksResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutLinksResponse'] = ResolversParentTypes['PayoutLinksResponse'],
> = {
  hasMore?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  payoutLinks?: Resolver<Array<ResolversTypes['PayoutLink']>, ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutPurposeResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutPurpose'] = ResolversParentTypes['PayoutPurpose'],
> = {
  label?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['PayoutPurposeTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutPurposeCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutPurposeCreateResponse'] = ResolversParentTypes['PayoutPurposeCreateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutRejectBulkResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutRejectBulkResponse'] = ResolversParentTypes['PayoutRejectBulkResponse'],
> = {
  __resolveType: TypeResolveFn<
    'PayoutRejectBulkResponseFailure' | 'PayoutRejectBulkResponseSuccess',
    ParentType,
    ContextType
  >;
};

export type PayoutRejectBulkResponseFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutRejectBulkResponseFailure'] = ResolversParentTypes['PayoutRejectBulkResponseFailure'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  failedPayoutIds?: Resolver<Maybe<Array<ResolversTypes['ID']>>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutRejectBulkResponseSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutRejectBulkResponseSuccess'] = ResolversParentTypes['PayoutRejectBulkResponseSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutSourceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutSource'] = ResolversParentTypes['PayoutSource'],
> = {
  priority?: Resolver<Maybe<ResolversTypes['BigInt']>, ParentType, ContextType>;
  sourceId?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  sourceType?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutWorkflowResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutWorkflow'] = ResolversParentTypes['PayoutWorkflow'],
> = {
  __resolveType: TypeResolveFn<'PayoutWorkflowHistory' | 'Workflow', ParentType, ContextType>;
};

export type PayoutWorkflowHistoryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutWorkflowHistory'] = ResolversParentTypes['PayoutWorkflowHistory'],
> = {
  currentLevel?: Resolver<ResolversTypes['Int'], ParentType, ContextType>;
  steps?: Resolver<Array<ResolversTypes['PayoutWorkflowStep']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutWorkflowRoleResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutWorkflowRole'] = ResolversParentTypes['PayoutWorkflowRole'],
> = {
  checkers?: Resolver<
    Maybe<Array<ResolversTypes['PayoutWorkflowRoleChecker']>>,
    ParentType,
    ContextType
  >;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  reviewerCount?: Resolver<ResolversTypes['Int'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['PayoutWorkflowRoleTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutWorkflowRoleCheckerResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutWorkflowRoleChecker'] = ResolversParentTypes['PayoutWorkflowRoleChecker'],
> = {
  approved?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  comment?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  email?: Resolver<Maybe<ResolversTypes['EmailAddress']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  user?: Resolver<Maybe<ResolversTypes['User']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutWorkflowStepResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutWorkflowStep'] = ResolversParentTypes['PayoutWorkflowStep'],
> = {
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  level?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  operationType?: Resolver<
    Maybe<ResolversTypes['PayoutWorkflowStepOperationTypeEnum']>,
    ParentType,
    ContextType
  >;
  roles?: Resolver<Array<ResolversTypes['PayoutWorkflowRole']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsPendingSummaryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsPendingSummary'] = ResolversParentTypes['PayoutsPendingSummary'],
> = {
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsQueuedSummaryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsQueuedSummary'] = ResolversParentTypes['PayoutsQueuedSummary'],
> = {
  __resolveType: TypeResolveFn<
    | 'PayoutsQueuedSummaryBeneficiaryBankDown'
    | 'PayoutsQueuedSummaryLowBalance'
    | 'PayoutsQueuedSummaryNEFTLimitExhausted'
    | 'PayoutsQueuedSummaryNEFTWindowClosed'
    | 'PayoutsQueuedSummaryNPCISystemDown'
    | 'PayoutsQueuedSummaryWithoutReason',
    ParentType,
    ContextType
  >;
};

export type PayoutsQueuedSummaryBeneficiaryBankDownResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsQueuedSummaryBeneficiaryBankDown'] = ResolversParentTypes['PayoutsQueuedSummaryBeneficiaryBankDown'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsQueuedSummaryLowBalanceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsQueuedSummaryLowBalance'] = ResolversParentTypes['PayoutsQueuedSummaryLowBalance'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsQueuedSummaryNeftLimitExhaustedResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsQueuedSummaryNEFTLimitExhausted'] = ResolversParentTypes['PayoutsQueuedSummaryNEFTLimitExhausted'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsQueuedSummaryNeftWindowClosedResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsQueuedSummaryNEFTWindowClosed'] = ResolversParentTypes['PayoutsQueuedSummaryNEFTWindowClosed'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsQueuedSummaryNpciSystemDownResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsQueuedSummaryNPCISystemDown'] = ResolversParentTypes['PayoutsQueuedSummaryNPCISystemDown'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsQueuedSummaryWithoutReasonResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsQueuedSummaryWithoutReason'] = ResolversParentTypes['PayoutsQueuedSummaryWithoutReason'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsResponse'] = ResolversParentTypes['PayoutsResponse'],
> = {
  hasMore?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  payouts?: Resolver<Array<ResolversTypes['Payout']>, ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsScheduledSummaryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsScheduledSummary'] = ResolversParentTypes['PayoutsScheduledSummary'],
> = {
  __resolveType: TypeResolveFn<
    | 'PayoutsScheduledSummaryAllTime'
    | 'PayoutsScheduledSummaryNextMonth'
    | 'PayoutsScheduledSummaryNextTwoDays'
    | 'PayoutsScheduledSummaryNextWeek'
    | 'PayoutsScheduledSummaryToday',
    ParentType,
    ContextType
  >;
};

export type PayoutsScheduledSummaryAllTimeResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsScheduledSummaryAllTime'] = ResolversParentTypes['PayoutsScheduledSummaryAllTime'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsScheduledSummaryNextMonthResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsScheduledSummaryNextMonth'] = ResolversParentTypes['PayoutsScheduledSummaryNextMonth'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsScheduledSummaryNextTwoDaysResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsScheduledSummaryNextTwoDays'] = ResolversParentTypes['PayoutsScheduledSummaryNextTwoDays'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsScheduledSummaryNextWeekResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsScheduledSummaryNextWeek'] = ResolversParentTypes['PayoutsScheduledSummaryNextWeek'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsScheduledSummaryTodayResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsScheduledSummaryToday'] = ResolversParentTypes['PayoutsScheduledSummaryToday'],
> = {
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  count?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  totalAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  totalFees?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PayoutsSummaryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PayoutsSummary'] = ResolversParentTypes['PayoutsSummary'],
> = {
  bankingAccount?: Resolver<ResolversTypes['MerchantBankingAccount'], ParentType, ContextType>;
  pending?: Resolver<ResolversTypes['PayoutsPendingSummary'], ParentType, ContextType>;
  queued?: Resolver<Array<ResolversTypes['PayoutsQueuedSummary']>, ParentType, ContextType>;
  scheduled?: Resolver<Array<ResolversTypes['PayoutsScheduledSummary']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PhoneResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Phone'] = ResolversParentTypes['Phone'],
> = {
  countryCode?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  number?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PointOfSaleResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PointOfSale'] = ResolversParentTypes['PointOfSale'],
> = {
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  ssoIdentifier?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  url?: Resolver<ResolversTypes['URL'], ParentType, ContextType>;
  userId?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PointOfSaleKeyFetchResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PointOfSaleKeyFetchResponse'] = ResolversParentTypes['PointOfSaleKeyFetchResponse'],
> = {
  accessKey?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  secretKey?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PointOfSalePaymentCreateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PointOfSalePaymentCreateFailureResponse'] = ResolversParentTypes['PointOfSalePaymentCreateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PointOfSalePaymentCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PointOfSalePaymentCreateResponse'] = ResolversParentTypes['PointOfSalePaymentCreateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'PointOfSalePaymentCreateFailureResponse' | 'PointOfSalePaymentCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type PointOfSalePaymentCreateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PointOfSalePaymentCreateSuccessResponse'] = ResolversParentTypes['PointOfSalePaymentCreateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  pointOfSale?: Resolver<ResolversTypes['PointOfSale'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PointOfSalePaymentUpdateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PointOfSalePaymentUpdateFailureResponse'] = ResolversParentTypes['PointOfSalePaymentUpdateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  gatewayErrorCode?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  gatewayErrorDescription?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type PointOfSalePaymentUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PointOfSalePaymentUpdateResponse'] = ResolversParentTypes['PointOfSalePaymentUpdateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'PointOfSalePaymentUpdateFailureResponse' | 'PointOfSalePaymentUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type PointOfSalePaymentUpdateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['PointOfSalePaymentUpdateSuccessResponse'] = ResolversParentTypes['PointOfSalePaymentUpdateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export interface PositiveIntScalarConfig
  extends GraphQLScalarTypeConfig<ResolversTypes['PositiveInt'], any> {
  name: 'PositiveInt';
}

export type QrCodeResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['QRCode'] = ResolversParentTypes['QRCode'],
> = {
  dates?: Resolver<ResolversTypes['QRCodeDate'], ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  imageURL?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  isFixedAmount?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  paymentDetails?: Resolver<ResolversTypes['QRCodePaymentDetail'], ParentType, ContextType>;
  status?: Resolver<ResolversTypes['QRCodeStatusEnum'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['QRCodeTypeEnum'], ParentType, ContextType>;
  usage?: Resolver<ResolversTypes['QRCodeUsageEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type QrCodeCreateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['QRCodeCreateFailureResponse'] = ResolversParentTypes['QRCodeCreateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type QrCodeCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['QRCodeCreateResponse'] = ResolversParentTypes['QRCodeCreateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'QRCodeCreateFailureResponse' | 'QRCodeCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type QrCodeCreateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['QRCodeCreateSuccessResponse'] = ResolversParentTypes['QRCodeCreateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  qrCode?: Resolver<ResolversTypes['QRCode'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type QrCodeDateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['QRCodeDate'] = ResolversParentTypes['QRCodeDate'],
> = {
  createdAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type QrCodePaymentDetailResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['QRCodePaymentDetail'] = ResolversParentTypes['QRCodePaymentDetail'],
> = {
  paymentAmount?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  paymentAmountReceived?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  paymentsReceivedCount?: Resolver<
    Maybe<ResolversTypes['NonNegativeInt']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type QrCodesResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['QRCodesResponse'] = ResolversParentTypes['QRCodesResponse'],
> = {
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  qrCodes?: Resolver<Array<ResolversTypes['QRCode']>, ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type QueryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Query'] = ResolversParentTypes['Query'],
> = {
  aadhaarCaptcha?: Resolver<ResolversTypes['AadhaarCaptchaResponse'], ParentType, ContextType>;
  aadhaarCaptchaV2?: Resolver<ResolversTypes['AadhaarCaptchaV2Response'], ParentType, ContextType>;
  addressByPincode?: Resolver<
    ResolversTypes['AddressByPincodeResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryAddressByPincodeArgs, 'pincode'>
  >;
  bankDetails?: Resolver<
    ResolversTypes['Bank'],
    ParentType,
    ContextType,
    RequireFields<QueryBankDetailsArgs, 'ifsc'>
  >;
  failedPaymentsOverview?: Resolver<
    ResolversTypes['FailedPaymentsOverviewResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryFailedPaymentsOverviewArgs, 'entity' | 'fromDate' | 'limit' | 'toDate'>
  >;
  invoiceById?: Resolver<
    ResolversTypes['Invoice'],
    ParentType,
    ContextType,
    RequireFields<QueryInvoiceByIdArgs, 'id'>
  >;
  invoices?: Resolver<
    ResolversTypes['InvoicesResponse'],
    ParentType,
    ContextType,
    Partial<QueryInvoicesArgs>
  >;
  merchantAnalytics?: Resolver<ResolversTypes['MerchantAnalytics'], ParentType, ContextType>;
  merchantBalanceById?: Resolver<
    ResolversTypes['MerchantBalance'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantBalanceByIdArgs, 'id'>
  >;
  merchantBankDetails?: Resolver<
    ResolversTypes['MerchantBankDetailsResponse'],
    ParentType,
    ContextType
  >;
  merchantBankingAccountsBalance?: Resolver<
    ResolversTypes['MerchantBankingAccountsBalanceResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantBankingAccountsBalanceArgs, 'type'>
  >;
  merchantBankingRoles?: Resolver<
    Array<Maybe<ResolversTypes['MerchantBankingRole']>>,
    ParentType,
    ContextType
  >;
  merchantBusinessCategories?: Resolver<
    Array<ResolversTypes['MerchantBusinessCategoriesResponse']>,
    ParentType,
    ContextType,
    Partial<QueryMerchantBusinessCategoriesArgs>
  >;
  merchantBusinessParentCategories?: Resolver<
    Array<ResolversTypes['MerchantBusinessParentCategory']>,
    ParentType,
    ContextType
  >;
  merchantBusinessTypes?: Resolver<
    ResolversTypes['MerchantBusinessTypesResponse'],
    ParentType,
    ContextType
  >;
  merchantById?: Resolver<
    ResolversTypes['Merchant'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantByIdArgs, 'id'>
  >;
  merchantClarificationDetails?: Resolver<
    Maybe<ResolversTypes['MerchantClarificationDetailsResponse']>,
    ParentType,
    ContextType
  >;
  merchantConfig?: Resolver<
    ResolversTypes['MerchantConfig'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantConfigArgs, 'namespace'>
  >;
  merchantConsent?: Resolver<
    ResolversTypes['MerchantConsentResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantConsentArgs, 'partnerId'>
  >;
  merchantContactById?: Resolver<
    Maybe<ResolversTypes['MerchantContact']>,
    ParentType,
    ContextType,
    RequireFields<QueryMerchantContactByIdArgs, 'id'>
  >;
  merchantContactFundAccounts?: Resolver<
    ResolversTypes['MerchantContactFundAccountsResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantContactFundAccountsArgs, 'contactId' | 'limit' | 'offset'>
  >;
  merchantContactTypes?: Resolver<Array<ResolversTypes['String']>, ParentType, ContextType>;
  merchantContacts?: Resolver<
    ResolversTypes['MerchantContactsResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantContactsArgs, 'active'>
  >;
  merchantCreditBalance?: Resolver<
    ResolversTypes['MerchantCreditBalanceResponse'],
    ParentType,
    ContextType
  >;
  merchantDocumentById?: Resolver<
    ResolversTypes['MerchantDocumentByIdResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantDocumentByIdArgs, 'documentId'>
  >;
  merchantFeatureFlags?: Resolver<
    Maybe<Array<ResolversTypes['MerchantFeatureFlag']>>,
    ParentType,
    ContextType,
    RequireFields<QueryMerchantFeatureFlagsArgs, 'names'>
  >;
  merchantGstins?: Resolver<Array<ResolversTypes['String']>, ParentType, ContextType>;
  merchantIdentity?: Resolver<
    Maybe<Array<ResolversTypes['MerchantIdentityResponse']>>,
    ParentType,
    ContextType,
    RequireFields<QueryMerchantIdentityArgs, 'searchQuery'>
  >;
  merchantKYCPartnerAccess?: Resolver<
    ResolversTypes['MerchantKYCPartnerAccessResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantKycPartnerAccessArgs, 'referralCode'>
  >;
  merchantNeedsClarificationEligibility?: Resolver<
    ResolversTypes['MerchantNcEligibilityResponse'],
    ParentType,
    ContextType
  >;
  merchantOnboardingQuestionDetails?: Resolver<
    ResolversTypes['MerchantOnboardingQuestionDetailsResponse'],
    ParentType,
    ContextType
  >;
  merchantPaymentHandle?: Resolver<
    ResolversTypes['MerchantPaymentHandleResponse'],
    ParentType,
    ContextType
  >;
  merchantPaymentHandleAvailability?: Resolver<
    ResolversTypes['MerchantPaymentHandleAvailabilityResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantPaymentHandleAvailabilityArgs, 'paymentHandleSlug'>
  >;
  merchantPaymentHandleSuggestions?: Resolver<
    ResolversTypes['MerchantPaymentHandleSuggestionsResponse'],
    ParentType,
    ContextType,
    Partial<QueryMerchantPaymentHandleSuggestionsArgs>
  >;
  merchantPolicy?: Resolver<
    ResolversTypes['MerchantPolicyResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantPolicyArgs, 'publishedUrl' | 'section'>
  >;
  merchantPolicyPreview?: Resolver<
    ResolversTypes['MerchantPolicyPreviewResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantPolicyPreviewArgs, 'section'>
  >;
  merchantPolicyPreviewV2?: Resolver<
    ResolversTypes['MerchantPolicyPreviewV2Response'],
    ParentType,
    ContextType
  >;
  merchantPolicyWizardV2Eligibility?: Resolver<
    ResolversTypes['MerchantPolicyWizardV2EligibilityResponse'],
    ParentType,
    ContextType
  >;
  merchantPreferences?: Resolver<
    Array<Maybe<ResolversTypes['MerchantPreference']>>,
    ParentType,
    ContextType,
    RequireFields<QueryMerchantPreferencesArgs, 'preferenceGroup'>
  >;
  merchantReferral?: Resolver<ResolversTypes['MerchantReferralResponse'], ParentType, ContextType>;
  merchantSelfServeWorkflowStatus?: Resolver<
    ResolversTypes['MerchantSelfServeWorkflowStatusResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantSelfServeWorkflowStatusArgs, 'workflow'>
  >;
  merchantSettlementConfig?: Resolver<
    ResolversTypes['MerchantSettlementConfigResponse'],
    ParentType,
    ContextType
  >;
  merchantSupportDetails?: Resolver<
    ResolversTypes['MerchantSupportDetailsResponse'],
    ParentType,
    ContextType
  >;
  merchantValidateSocialMediaUrl?: Resolver<
    ResolversTypes['MerchantValidateSocialMediaURLResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryMerchantValidateSocialMediaUrlArgs, 'platform' | 'url'>
  >;
  merchantWebsites?: Resolver<ResolversTypes['MerchantWebsitesResponse'], ParentType, ContextType>;
  organisationInformation?: Resolver<
    ResolversTypes['Organisation'],
    ParentType,
    ContextType,
    RequireFields<QueryOrganisationInformationArgs, 'domainName'>
  >;
  organisationInformationByDomain?: Resolver<
    ResolversTypes['Organisation'],
    ParentType,
    ContextType,
    RequireFields<QueryOrganisationInformationByDomainArgs, 'domain'>
  >;
  partnerConfigById?: Resolver<
    ResolversTypes['PartnerConfigResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryPartnerConfigByIdArgs, 'id'>
  >;
  paymentAnalytics?: Resolver<
    ResolversTypes['PaymentAnalyticsResponse'],
    ParentType,
    ContextType,
    RequireFields<
      QueryPaymentAnalyticsArgs,
      'aggregateBy' | 'columnName' | 'fromDate' | 'indexName' | 'toDate'
    >
  >;
  paymentById?: Resolver<
    ResolversTypes['Payment'],
    ParentType,
    ContextType,
    RequireFields<QueryPaymentByIdArgs, 'id'>
  >;
  paymentInstantRefundEligibility?: Resolver<
    ResolversTypes['PaymentInstantRefundEligibilityResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryPaymentInstantRefundEligibilityArgs, 'amount' | 'id'>
  >;
  paymentLinkById?: Resolver<
    ResolversTypes['PaymentLink'],
    ParentType,
    ContextType,
    RequireFields<QueryPaymentLinkByIdArgs, 'id'>
  >;
  paymentLinks?: Resolver<
    ResolversTypes['PaymentLinksResponse'],
    ParentType,
    ContextType,
    Partial<QueryPaymentLinksArgs>
  >;
  paymentOverview?: Resolver<
    ResolversTypes['PaymentOverviewResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryPaymentOverviewArgs, 'fromDate' | 'toDate'>
  >;
  paymentPageById?: Resolver<
    ResolversTypes['PaymentPage'],
    ParentType,
    ContextType,
    RequireFields<QueryPaymentPageByIdArgs, 'id'>
  >;
  paymentPageTransactionsById?: Resolver<
    Maybe<ResolversTypes['PaymentPageTransactionResponse']>,
    ParentType,
    ContextType,
    RequireFields<QueryPaymentPageTransactionsByIdArgs, 'paymentPageId'>
  >;
  paymentPages?: Resolver<
    Maybe<ResolversTypes['PaymentPagesResponse']>,
    ParentType,
    ContextType,
    Partial<QueryPaymentPagesArgs>
  >;
  paymentSummary?: Resolver<
    ResolversTypes['PaymentSummaryResponse'],
    ParentType,
    ContextType,
    RequireFields<
      QueryPaymentSummaryArgs,
      | 'fromDate'
      | 'paymentIndexName'
      | 'paymentsAggregateBy'
      | 'paymentsAggregationField'
      | 'toDate'
    >
  >;
  payments?: Resolver<
    ResolversTypes['PaymentsResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryPaymentsArgs, 'limit' | 'offset'>
  >;
  paymentsWidgets?: Resolver<ResolversTypes['PaymentsWidgets'], ParentType, ContextType>;
  payoutBatchById?: Resolver<
    ResolversTypes['PayoutBatch'],
    ParentType,
    ContextType,
    RequireFields<QueryPayoutBatchByIdArgs, 'payoutBatchId'>
  >;
  payoutBatches?: Resolver<
    ResolversTypes['PayoutBatchesResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryPayoutBatchesArgs, 'limit' | 'offset'>
  >;
  payoutById?: Resolver<
    ResolversTypes['Payout'],
    ParentType,
    ContextType,
    RequireFields<QueryPayoutByIdArgs, 'id'>
  >;
  payoutLinkById?: Resolver<
    ResolversTypes['PayoutLink'],
    ParentType,
    ContextType,
    RequireFields<QueryPayoutLinkByIdArgs, 'id'>
  >;
  payoutLinks?: Resolver<
    ResolversTypes['PayoutLinksResponse'],
    ParentType,
    ContextType,
    Partial<QueryPayoutLinksArgs>
  >;
  payoutPurposes?: Resolver<Array<ResolversTypes['PayoutPurpose']>, ParentType, ContextType>;
  payouts?: Resolver<
    ResolversTypes['PayoutsResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryPayoutsArgs, 'limit' | 'offset'>
  >;
  payoutsSummary?: Resolver<Array<ResolversTypes['PayoutsSummary']>, ParentType, ContextType>;
  payoutsWorkflowConfig?: Resolver<
    Maybe<ResolversTypes['WorkflowConfig']>,
    ParentType,
    ContextType,
    RequireFields<QueryPayoutsWorkflowConfigArgs, 'configType'>
  >;
  pointOfSaleKeysFetch?: Resolver<
    ResolversTypes['PointOfSaleKeyFetchResponse'],
    ParentType,
    ContextType
  >;
  qrCodes?: Resolver<
    ResolversTypes['QRCodesResponse'],
    ParentType,
    ContextType,
    Partial<QueryQrCodesArgs>
  >;
  refundById?: Resolver<
    ResolversTypes['PaymentRefund'],
    ParentType,
    ContextType,
    RequireFields<QueryRefundByIdArgs, 'id'>
  >;
  refunds?: Resolver<
    ResolversTypes['RefundsResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryRefundsArgs, 'limit' | 'offset'>
  >;
  settlementById?: Resolver<
    ResolversTypes['Settlement'],
    ParentType,
    ContextType,
    RequireFields<QuerySettlementByIdArgs, 'id'>
  >;
  settlementByUtr?: Resolver<
    Array<Maybe<ResolversTypes['Settlement']>>,
    ParentType,
    ContextType,
    RequireFields<QuerySettlementByUtrArgs, 'utr'>
  >;
  settlementCycle?: Resolver<ResolversTypes['SettlementCycle'], ParentType, ContextType>;
  settlements?: Resolver<
    ResolversTypes['SettlementsResponse'],
    ParentType,
    ContextType,
    Partial<QuerySettlementsArgs>
  >;
  smsNotificationStatus?: Resolver<
    ResolversTypes['SmsNotificationStatusResponse'],
    ParentType,
    ContextType
  >;
  tdsCategories?: Resolver<Array<ResolversTypes['TDSCategory']>, ParentType, ContextType>;
  transactionById?: Resolver<
    ResolversTypes['Transaction'],
    ParentType,
    ContextType,
    RequireFields<QueryTransactionByIdArgs, 'id'>
  >;
  transactions?: Resolver<
    ResolversTypes['TransactionsResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryTransactionsArgs, 'accountNumber' | 'limit' | 'offset'>
  >;
  twoFactorPasswordEnabled?: Resolver<
    ResolversTypes['TwoFactorPasswordEnabledResponse'],
    ParentType,
    ContextType
  >;
  userAuthentication?: Resolver<ResolversTypes['UserAuthentication'], ParentType, ContextType>;
  userById?: Resolver<
    ResolversTypes['User'],
    ParentType,
    ContextType,
    RequireFields<QueryUserByIdArgs, 'id'>
  >;
  validateVpa?: Resolver<
    ResolversTypes['ValidateVpaResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryValidateVpaArgs, 'vpa'>
  >;
  vendorPaymentById?: Resolver<
    ResolversTypes['VendorPayment'],
    ParentType,
    ContextType,
    RequireFields<QueryVendorPaymentByIdArgs, 'id'>
  >;
  vendorPayments?: Resolver<
    ResolversTypes['VendorPaymentsResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryVendorPaymentsArgs, 'limit' | 'offset'>
  >;
  whatsappNotificationStatus?: Resolver<
    ResolversTypes['WhatsappNotificationStatusResponse'],
    ParentType,
    ContextType,
    RequireFields<QueryWhatsappNotificationStatusArgs, 'source'>
  >;
};

export type RecentTransactionsWidgetResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RecentTransactionsWidget'] = ResolversParentTypes['RecentTransactionsWidget'],
> = {
  title?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['PaymentsWidgetTypeEnum'], ParentType, ContextType>;
  variant?: Resolver<ResolversTypes['WidgetVariantEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RefreshAccessTokenResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RefreshAccessToken'] = ResolversParentTypes['RefreshAccessToken'],
> = {
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RefundsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RefundsResponse'] = ResolversParentTypes['RefundsResponse'],
> = {
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  refunds?: Resolver<Array<ResolversTypes['PaymentRefund']>, ParentType, ContextType>;
  total?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterBusinessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterBusinessResponse'] = ResolversParentTypes['RegisterBusinessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterEmailResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterEmail'] = ResolversParentTypes['RegisterEmail'],
> = {
  __resolveType: TypeResolveFn<
    'RegisterEmailError' | 'RegisterEmailSuccess',
    ParentType,
    ContextType
  >;
};

export type RegisterEmailErrorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterEmailError'] = ResolversParentTypes['RegisterEmailError'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterEmailSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterEmailSuccess'] = ResolversParentTypes['RegisterEmailSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  email?: Resolver<ResolversTypes['EmailAddress'], ParentType, ContextType>;
  merchantId?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  userId?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterEmailVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterEmailVerifyResponse'] = ResolversParentTypes['RegisterEmailVerifyResponse'],
> = {
  __resolveType: TypeResolveFn<
    'RegisterEmailVerifyResponseFailure' | 'RegisterEmailVerifyResponseSuccess',
    ParentType,
    ContextType
  >;
};

export type RegisterEmailVerifyResponseFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterEmailVerifyResponseFailure'] = ResolversParentTypes['RegisterEmailVerifyResponseFailure'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterEmailVerifyResponseSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterEmailVerifyResponseSuccess'] = ResolversParentTypes['RegisterEmailVerifyResponseSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  user?: Resolver<ResolversTypes['User'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterFcmTokenResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterFCMTokenResponse'] = ResolversParentTypes['RegisterFCMTokenResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterMerchantResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterMerchantResponse'] = ResolversParentTypes['RegisterMerchantResponse'],
> = {
  __resolveType: TypeResolveFn<
    'RegisterMerchantResponseFailure' | 'RegisterMerchantResponseSuccess',
    ParentType,
    ContextType
  >;
};

export type RegisterMerchantResponseFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterMerchantResponseFailure'] = ResolversParentTypes['RegisterMerchantResponseFailure'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<Maybe<ResolversTypes['RegisterMerchantErrorEnum']>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterMerchantResponseSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterMerchantResponseSuccess'] = ResolversParentTypes['RegisterMerchantResponseSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterMobileVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterMobileVerifyResponse'] = ResolversParentTypes['RegisterMobileVerifyResponse'],
> = {
  __resolveType: TypeResolveFn<
    'RegisterMobileVerifyResponseFailure' | 'RegisterMobileVerifyResponseSuccess',
    ParentType,
    ContextType
  >;
};

export type RegisterMobileVerifyResponseFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterMobileVerifyResponseFailure'] = ResolversParentTypes['RegisterMobileVerifyResponseFailure'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<Maybe<ResolversTypes['RegisterMobileVerifyEnum']>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterMobileVerifyResponseSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterMobileVerifyResponseSuccess'] = ResolversParentTypes['RegisterMobileVerifyResponseSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  user?: Resolver<ResolversTypes['User'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterOAuthResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterOAuth'] = ResolversParentTypes['RegisterOAuth'],
> = {
  __resolveType: TypeResolveFn<
    | 'AuthUser'
    | 'RegisterOAuthEmailError'
    | 'RegisterOAuthEmailExist'
    | 'RegisterOAuthInvalidTokenError',
    ParentType,
    ContextType
  >;
};

export type RegisterOAuthEmailErrorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterOAuthEmailError'] = ResolversParentTypes['RegisterOAuthEmailError'],
> = {
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterOAuthEmailExistResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterOAuthEmailExist'] = ResolversParentTypes['RegisterOAuthEmailExist'],
> = {
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RegisterOAuthInvalidTokenErrorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RegisterOAuthInvalidTokenError'] = ResolversParentTypes['RegisterOAuthInvalidTokenError'],
> = {
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RejectPayoutBatchResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RejectPayoutBatchResponse'] = ResolversParentTypes['RejectPayoutBatchResponse'],
> = {
  __resolveType: TypeResolveFn<
    'RejectPayoutBatchResponseFailure' | 'RejectPayoutBatchResponseSuccess',
    ParentType,
    ContextType
  >;
};

export type RejectPayoutBatchResponseFailureResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RejectPayoutBatchResponseFailure'] = ResolversParentTypes['RejectPayoutBatchResponseFailure'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  failedPayoutBatchIds?: Resolver<Maybe<Array<ResolversTypes['ID']>>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RejectPayoutBatchResponseSuccessResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RejectPayoutBatchResponseSuccess'] = ResolversParentTypes['RejectPayoutBatchResponseSuccess'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type RejectPayoutResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['RejectPayoutResponse'] = ResolversParentTypes['RejectPayoutResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ResendEmailOtpResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ResendEmailOtp'] = ResolversParentTypes['ResendEmailOtp'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ResendTwoFactorLoginOtpResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ResendTwoFactorLoginOtpResponse'] = ResolversParentTypes['ResendTwoFactorLoginOtpResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ResetPasswordEmailResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ResetPasswordEmail'] = ResolversParentTypes['ResetPasswordEmail'],
> = {
  emailSent?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SendApprovePayoutBatchOtpResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SendApprovePayoutBatchOtp'] = ResolversParentTypes['SendApprovePayoutBatchOtp'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SendApprovePayoutOtpResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SendApprovePayoutOtp'] = ResolversParentTypes['SendApprovePayoutOtp'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SendCreatePayoutLinkOtpResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SendCreatePayoutLinkOtp'] = ResolversParentTypes['SendCreatePayoutLinkOtp'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SendCreatePayoutOtpResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SendCreatePayoutOtp'] = ResolversParentTypes['SendCreatePayoutOtp'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SendIciciPayoutOtpResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SendIciciPayoutOtpResponse'] = ResolversParentTypes['SendIciciPayoutOtpResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SendPayoutApproveBulkOtpResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SendPayoutApproveBulkOtp'] = ResolversParentTypes['SendPayoutApproveBulkOtp'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SendPayoutCompositeOtpResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SendPayoutCompositeOtp'] = ResolversParentTypes['SendPayoutCompositeOtp'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SettlementResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Settlement'] = ResolversParentTypes['Settlement'],
> = {
  amount?: Resolver<ResolversTypes['SettlementAmount'], ParentType, ContextType>;
  breakUp?: Resolver<Maybe<Array<ResolversTypes['SettlementBreakup']>>, ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  status?: Resolver<ResolversTypes['SettlementStatusEnum'], ParentType, ContextType>;
  transactionSources?: Resolver<
    Maybe<Array<Maybe<ResolversTypes['TransactionSourceDetails']>>>,
    ParentType,
    ContextType,
    RequireFields<SettlementTransactionSourcesArgs, 'sourceType'>
  >;
  utr?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SettlementAmountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SettlementAmount'] = ResolversParentTypes['SettlementAmount'],
> = {
  fee?: Resolver<Maybe<ResolversTypes['Float']>, ParentType, ContextType>;
  settlement?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  tax?: Resolver<Maybe<ResolversTypes['Float']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SettlementBreakupResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SettlementBreakup'] = ResolversParentTypes['SettlementBreakup'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  component?: Resolver<
    Maybe<ResolversTypes['SettlementBreakupComponentEnum']>,
    ParentType,
    ContextType
  >;
  count?: Resolver<Maybe<ResolversTypes['PositiveInt']>, ParentType, ContextType>;
  transactionType?: Resolver<
    Maybe<ResolversTypes['SettlementBreakupTransactionTypeEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SettlementCycleResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SettlementCycle'] = ResolversParentTypes['SettlementCycle'],
> = {
  accountBalance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  amountToBeSettled?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  isOnHold?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  nextSettlementOn?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  reason?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SettlementsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SettlementsResponse'] = ResolversParentTypes['SettlementsResponse'],
> = {
  hasMore?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  settlements?: Resolver<Array<ResolversTypes['Settlement']>, ParentType, ContextType>;
  total?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SettlementsWidgetResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SettlementsWidget'] = ResolversParentTypes['SettlementsWidget'],
> = {
  description?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  title?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['PaymentsWidgetTypeEnum'], ParentType, ContextType>;
  variant?: Resolver<ResolversTypes['WidgetVariantEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SmsNotificationStatusResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SmsNotificationStatusResponse'] = ResolversParentTypes['SmsNotificationStatusResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isEnabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type SmsNotificationToggleResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['SmsNotificationToggle'] = ResolversParentTypes['SmsNotificationToggle'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TdsCategoryResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TDSCategory'] = ResolversParentTypes['TDSCategory'],
> = {
  code?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['Int'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  rate?: Resolver<ResolversTypes['Float'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Transaction'] = ResolversParentTypes['Transaction'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  balance?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  bankingAccountNumber?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  source?: Resolver<ResolversTypes['TransactionSource'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['TransactionTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionAmountResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionAmount'] = ResolversParentTypes['TransactionAmount'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  amountRefunded?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  amountTransferred?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  baseAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionErrorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionError'] = ResolversParentTypes['TransactionError'],
> = {
  errorCode?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  errorDescription?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  errorReason?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  errorSource?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  errorStep?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionSourceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionSource'] = ResolversParentTypes['TransactionSource'],
> = {
  __resolveType: TypeResolveFn<
    | 'Payout'
    | 'TransactionSourceAdjustment'
    | 'TransactionSourceBankTransfer'
    | 'TransactionSourceExternal'
    | 'TransactionSourceFundAccountValidation'
    | 'TransactionSourceReversal',
    ParentType,
    ContextType
  >;
};

export type TransactionSourceAdjustmentResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionSourceAdjustment'] = ResolversParentTypes['TransactionSourceAdjustment'],
> = {
  description?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  id?: Resolver<Maybe<ResolversTypes['ID']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionSourceBankTransferResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionSourceBankTransfer'] = ResolversParentTypes['TransactionSourceBankTransfer'],
> = {
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  mode?: Resolver<ResolversTypes['TransactionSourceBankTransferModeEnum'], ParentType, ContextType>;
  payee?: Resolver<ResolversTypes['TransactionSourceBankTransferPayee'], ParentType, ContextType>;
  payer?: Resolver<ResolversTypes['TransactionSourceBankTransferPayer'], ParentType, ContextType>;
  reference?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionSourceBankTransferPayeeResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionSourceBankTransferPayee'] = ResolversParentTypes['TransactionSourceBankTransferPayee'],
> = {
  accountNumber?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionSourceBankTransferPayerResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionSourceBankTransferPayer'] = ResolversParentTypes['TransactionSourceBankTransferPayer'],
> = {
  accountNumber?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  ifsc?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionSourceDetailsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionSourceDetails'] = ResolversParentTypes['TransactionSourceDetails'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  createdAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  fee?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  isInternational?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  status?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  tax?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionSourceExternalResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionSourceExternal'] = ResolversParentTypes['TransactionSourceExternal'],
> = {
  id?: Resolver<Maybe<ResolversTypes['ID']>, ParentType, ContextType>;
  utr?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionSourceFundAccountValidationResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionSourceFundAccountValidation'] = ResolversParentTypes['TransactionSourceFundAccountValidation'],
> = {
  fundAccount?: Resolver<
    Maybe<ResolversTypes['MerchantContactFundAccount']>,
    ParentType,
    ContextType
  >;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  utr?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionSourceReversalResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionSourceReversal'] = ResolversParentTypes['TransactionSourceReversal'],
> = {
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  payout?: Resolver<Maybe<ResolversTypes['Payout']>, ParentType, ContextType>;
  utr?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionStatusResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionStatus'] = ResolversParentTypes['TransactionStatus'],
> = {
  captured?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  status?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TransactionsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TransactionsResponse'] = ResolversParentTypes['TransactionsResponse'],
> = {
  hasMore?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  transactions?: Resolver<Array<ResolversTypes['Transaction']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorAddMobileOtpErrorResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorAddMobileOtpErrorResponse'] = ResolversParentTypes['TwoFactorAddMobileOtpErrorResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorAddMobileOtpResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorAddMobileOtpResponse'] = ResolversParentTypes['TwoFactorAddMobileOtpResponse'],
> = {
  __resolveType: TypeResolveFn<
    'TwoFactorAddMobileOtpErrorResponse' | 'TwoFactorAddMobileOtpSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type TwoFactorAddMobileOtpSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorAddMobileOtpSuccessResponse'] = ResolversParentTypes['TwoFactorAddMobileOtpSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  phone?: Resolver<ResolversTypes['Phone'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorAddMobileOtpVerifyErrorResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorAddMobileOtpVerifyErrorResponse'] = ResolversParentTypes['TwoFactorAddMobileOtpVerifyErrorResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorAddMobileOtpVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorAddMobileOtpVerifyResponse'] = ResolversParentTypes['TwoFactorAddMobileOtpVerifyResponse'],
> = {
  __resolveType: TypeResolveFn<
    'TwoFactorAddMobileOtpVerifyErrorResponse' | 'TwoFactorAddMobileOtpVerifySuccessResponse',
    ParentType,
    ContextType
  >;
};

export type TwoFactorAddMobileOtpVerifySuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorAddMobileOtpVerifySuccessResponse'] = ResolversParentTypes['TwoFactorAddMobileOtpVerifySuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  user?: Resolver<ResolversTypes['User'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorAuthUpdateFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorAuthUpdateFailureResponse'] = ResolversParentTypes['TwoFactorAuthUpdateFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorAuthUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorAuthUpdateResponse'] = ResolversParentTypes['TwoFactorAuthUpdateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'TwoFactorAuthUpdateFailureResponse' | 'TwoFactorAuthUpdateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type TwoFactorAuthUpdateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorAuthUpdateSuccessResponse'] = ResolversParentTypes['TwoFactorAuthUpdateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isTwoFactorEnabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorEmailOtpVerifyFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorEmailOtpVerifyFailureResponse'] = ResolversParentTypes['TwoFactorEmailOtpVerifyFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorEmailOtpVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorEmailOtpVerifyResponse'] = ResolversParentTypes['TwoFactorEmailOtpVerifyResponse'],
> = {
  __resolveType: TypeResolveFn<
    'TwoFactorEmailOtpVerifyFailureResponse' | 'TwoFactorEmailOtpVerifySuccessResponse',
    ParentType,
    ContextType
  >;
};

export type TwoFactorEmailOtpVerifySuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorEmailOtpVerifySuccessResponse'] = ResolversParentTypes['TwoFactorEmailOtpVerifySuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorOtpFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorOtpFailureResponse'] = ResolversParentTypes['TwoFactorOtpFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorOtpResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorOtpResponse'] = ResolversParentTypes['TwoFactorOtpResponse'],
> = {
  __resolveType: TypeResolveFn<
    'TwoFactorOtpFailureResponse' | 'TwoFactorOtpSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type TwoFactorOtpSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorOtpSuccessResponse'] = ResolversParentTypes['TwoFactorOtpSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  token?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorPasswordCreateErrorResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorPasswordCreateErrorResponse'] = ResolversParentTypes['TwoFactorPasswordCreateErrorResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorPasswordCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorPasswordCreateResponse'] = ResolversParentTypes['TwoFactorPasswordCreateResponse'],
> = {
  __resolveType: TypeResolveFn<
    'TwoFactorPasswordCreateErrorResponse' | 'TwoFactorPasswordCreateSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type TwoFactorPasswordCreateSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorPasswordCreateSuccessResponse'] = ResolversParentTypes['TwoFactorPasswordCreateSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  user?: Resolver<ResolversTypes['User'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorPasswordEnabledErrorResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorPasswordEnabledErrorResponse'] = ResolversParentTypes['TwoFactorPasswordEnabledErrorResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorPasswordEnabledResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorPasswordEnabledResponse'] = ResolversParentTypes['TwoFactorPasswordEnabledResponse'],
> = {
  __resolveType: TypeResolveFn<
    'TwoFactorPasswordEnabledErrorResponse' | 'TwoFactorPasswordEnabledSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type TwoFactorPasswordEnabledSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorPasswordEnabledSuccessResponse'] = ResolversParentTypes['TwoFactorPasswordEnabledSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isPasswordEnabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type TwoFactorUnverifiedMobileVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['TwoFactorUnverifiedMobileVerifyResponse'] = ResolversParentTypes['TwoFactorUnverifiedMobileVerifyResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export interface UrlScalarConfig extends GraphQLScalarTypeConfig<ResolversTypes['URL'], any> {
  name: 'URL';
}

export type UpdateMerchantConsentResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['UpdateMerchantConsentResponse'] = ResolversParentTypes['UpdateMerchantConsentResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export interface UploadScalarConfig extends GraphQLScalarTypeConfig<ResolversTypes['Upload'], any> {
  name: 'Upload';
}

export type UserResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['User'] = ResolversParentTypes['User'],
> = {
  email?: Resolver<Maybe<ResolversTypes['EmailAddress']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  isAccountLocked?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isAccountVerified?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isContactNumberVerified?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isEmailVerified?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isSignUpViaEmail?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isTwoFactorEnabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isTwoFactorEnforced?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  merchants?: Resolver<Array<ResolversTypes['Merchant']>, ParentType, ContextType>;
  name?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  phone?: Resolver<Maybe<ResolversTypes['Phone']>, ParentType, ContextType>;
  roles?: Resolver<
    Array<ResolversTypes['UserRole']>,
    ParentType,
    ContextType,
    Partial<UserRolesArgs>
  >;
  signupCampaign?: Resolver<
    Maybe<ResolversTypes['UserSignupCampaignEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type UserAuthenticationResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['UserAuthentication'] = ResolversParentTypes['UserAuthentication'],
> = {
  isAuthenticated?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  merchantId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  userId?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type UserContactDetailsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['UserContactDetails'] = ResolversParentTypes['UserContactDetails'],
> = {
  contact?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  email?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type UserContactDetailsUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['UserContactDetailsUpdateResponse'] = ResolversParentTypes['UserContactDetailsUpdateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type UserDeviceAnalyticsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['UserDeviceAnalyticsResponse'] = ResolversParentTypes['UserDeviceAnalyticsResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type UserLogoutResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['UserLogout'] = ResolversParentTypes['UserLogout'],
> = {
  isLoggedOut?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type UserOtpVerifyResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['UserOtpVerifyResponse'] = ResolversParentTypes['UserOtpVerifyResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  errorCode?: Resolver<
    Maybe<ResolversTypes['UserOtpVerifyErrorTypeEnum']>,
    ParentType,
    ContextType
  >;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type UserRoleResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['UserRole'] = ResolversParentTypes['UserRole'],
> = {
  banking?: Resolver<Maybe<ResolversTypes['UserRoleBankingEnum']>, ParentType, ContextType>;
  bankingPermissions?: Resolver<Array<Maybe<ResolversTypes['String']>>, ParentType, ContextType>;
  bankingRole?: Resolver<Maybe<ResolversTypes['MerchantBankingRole']>, ParentType, ContextType>;
  merchant?: Resolver<ResolversTypes['Merchant'], ParentType, ContextType>;
  payments?: Resolver<Maybe<ResolversTypes['UserRolePaymentsEnum']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export interface VpaScalarConfig extends GraphQLScalarTypeConfig<ResolversTypes['VPA'], any> {
  name: 'VPA';
}

export type ValidateVpaFailureResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ValidateVpaFailureResponse'] = ResolversParentTypes['ValidateVpaFailureResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type ValidateVpaResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ValidateVpaResponse'] = ResolversParentTypes['ValidateVpaResponse'],
> = {
  __resolveType: TypeResolveFn<
    'ValidateVpaFailureResponse' | 'ValidateVpaSuccessResponse',
    ParentType,
    ContextType
  >;
};

export type ValidateVpaSuccessResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['ValidateVpaSuccessResponse'] = ResolversParentTypes['ValidateVpaSuccessResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  customerName?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  vpa?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type VendorPaymentResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['VendorPayment'] = ResolversParentTypes['VendorPayment'],
> = {
  cancelledBy?: Resolver<Maybe<ResolversTypes['User']>, ParentType, ContextType>;
  createdBy?: Resolver<ResolversTypes['User'], ParentType, ContextType>;
  dates?: Resolver<ResolversTypes['VendorPaymentDates'], ParentType, ContextType>;
  description?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  fundAccount?: Resolver<
    Maybe<ResolversTypes['MerchantContactFundAccount']>,
    ParentType,
    ContextType
  >;
  gst?: Resolver<Maybe<ResolversTypes['VendorPaymentGST']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  invoice?: Resolver<ResolversTypes['VendorPaymentInvoice'], ParentType, ContextType>;
  merchantContact?: Resolver<Maybe<ResolversTypes['MerchantContact']>, ParentType, ContextType>;
  notes?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  payoutAmounts?: Resolver<
    Maybe<ResolversTypes['VendorPaymentPayoutAmounts']>,
    ParentType,
    ContextType
  >;
  payouts?: Resolver<
    Array<ResolversTypes['Payout']>,
    ParentType,
    ContextType,
    Partial<VendorPaymentPayoutsArgs>
  >;
  status?: Resolver<ResolversTypes['VendorPaymentStatusEnum'], ParentType, ContextType>;
  subtotal?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  tds?: Resolver<Maybe<ResolversTypes['VendorPaymentTDS']>, ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type VendorPaymentCancelResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['VendorPaymentCancelResponse'] = ResolversParentTypes['VendorPaymentCancelResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type VendorPaymentDatesResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['VendorPaymentDates'] = ResolversParentTypes['VendorPaymentDates'],
> = {
  cancelledAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  draftCreatedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  dueOn?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  invoiceIssuedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  paidAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  unpaidAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  updatedAt?: Resolver<Maybe<ResolversTypes['DateTime']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type VendorPaymentGstResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['VendorPaymentGST'] = ResolversParentTypes['VendorPaymentGST'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  gstin?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  type?: Resolver<ResolversTypes['GstTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type VendorPaymentInvoiceResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['VendorPaymentInvoice'] = ResolversParentTypes['VendorPaymentInvoice'],
> = {
  invoiceAttachment?: Resolver<
    Maybe<ResolversTypes['VendorPaymentInvoiceAttachment']>,
    ParentType,
    ContextType
  >;
  invoiceNumber?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type VendorPaymentInvoiceAttachmentResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['VendorPaymentInvoiceAttachment'] = ResolversParentTypes['VendorPaymentInvoiceAttachment'],
> = {
  fileId?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  mime?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  signedUrl?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type VendorPaymentPayoutAmountsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['VendorPaymentPayoutAmounts'] = ResolversParentTypes['VendorPaymentPayoutAmounts'],
> = {
  paidAmount?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  pendingAmount?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  processingAmount?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  scheduledAmount?: Resolver<Maybe<ResolversTypes['Money']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type VendorPaymentPayoutCreateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['VendorPaymentPayoutCreateResponse'] = ResolversParentTypes['VendorPaymentPayoutCreateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  vendorPayment?: Resolver<Maybe<ResolversTypes['VendorPayment']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type VendorPaymentTdsResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['VendorPaymentTDS'] = ResolversParentTypes['VendorPaymentTDS'],
> = {
  amount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  deductedAmount?: Resolver<ResolversTypes['Money'], ParentType, ContextType>;
  tdsCategory?: Resolver<ResolversTypes['TDSCategory'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type VendorPaymentsResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['VendorPaymentsResponse'] = ResolversParentTypes['VendorPaymentsResponse'],
> = {
  hasMore?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  limit?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: Resolver<ResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: Resolver<Maybe<ResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
  vendorPayments?: Resolver<Array<ResolversTypes['VendorPayment']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WhatsappNotificationStatusResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WhatsappNotificationStatusResponse'] = ResolversParentTypes['WhatsappNotificationStatusResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  isEnabled?: Resolver<Maybe<ResolversTypes['Boolean']>, ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WhatsappNotificationToggleResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WhatsappNotificationToggle'] = ResolversParentTypes['WhatsappNotificationToggle'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WidgetResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Widget'] = ResolversParentTypes['Widget'],
> = {
  __resolveType: TypeResolveFn<
    | 'AcceptPaymentsWidget'
    | 'OnboardingWidget'
    | 'PaymentAnalyticsWidget'
    | 'PaymentHandleWidget'
    | 'PaymentsWidgetError'
    | 'RecentTransactionsWidget'
    | 'SettlementsWidget',
    ParentType,
    ContextType
  >;
};

export type WorkflowResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['Workflow'] = ResolversParentTypes['Workflow'],
> = {
  config?: Resolver<ResolversTypes['WorkflowConfig'], ParentType, ContextType>;
  creator?: Resolver<ResolversTypes['WorkflowCreator'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  states?: Resolver<Maybe<Array<ResolversTypes['WorkflowState']>>, ParentType, ContextType>;
  status?: Resolver<ResolversTypes['WorkflowStatusEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowConfigResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowConfig'] = ResolversParentTypes['WorkflowConfig'],
> = {
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  enabled?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  template?: Resolver<ResolversTypes['WorkflowConfigTemplate'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowConfigStateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowConfigState'] = ResolversParentTypes['WorkflowConfigState'],
> = {
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  rule?: Resolver<ResolversTypes['WorkflowConfigStateRulePayout'], ParentType, ContextType>;
  transition?: Resolver<ResolversTypes['WorkflowConfigStateTransition'], ParentType, ContextType>;
  type?: Resolver<Maybe<ResolversTypes['WorkflowConfigStateTypeEnum']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowConfigStateRulePayoutResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowConfigStateRulePayout'] = ResolversParentTypes['WorkflowConfigStateRulePayout'],
> = {
  __resolveType: TypeResolveFn<
    | 'WorkflowConfigStateRulePayoutTypeBetween'
    | 'WorkflowConfigStateRulePayoutTypeChecker'
    | 'WorkflowConfigStateRulePayoutTypeMergeStates',
    ParentType,
    ContextType
  >;
};

export type WorkflowConfigStateRulePayoutTypeBetweenResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowConfigStateRulePayoutTypeBetween'] = ResolversParentTypes['WorkflowConfigStateRulePayoutTypeBetween'],
> = {
  key?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  max?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  min?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowConfigStateRulePayoutTypeCheckerResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowConfigStateRulePayoutTypeChecker'] = ResolversParentTypes['WorkflowConfigStateRulePayoutTypeChecker'],
> = {
  count?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  key?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  value?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowConfigStateRulePayoutTypeMergeStatesResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowConfigStateRulePayoutTypeMergeStates'] = ResolversParentTypes['WorkflowConfigStateRulePayoutTypeMergeStates'],
> = {
  states?: Resolver<Array<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowConfigStateTransitionResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowConfigStateTransition'] = ResolversParentTypes['WorkflowConfigStateTransition'],
> = {
  isEndState?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  isStartState?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  nextStates?: Resolver<Array<ResolversTypes['String']>, ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowConfigTemplateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowConfigTemplate'] = ResolversParentTypes['WorkflowConfigTemplate'],
> = {
  states?: Resolver<Array<ResolversTypes['WorkflowConfigState']>, ParentType, ContextType>;
  type?: Resolver<ResolversTypes['WorkflowConfigTemplateTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowCreatorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowCreator'] = ResolversParentTypes['WorkflowCreator'],
> = {
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['WorkflowCreatorTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowStateResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowState'] = ResolversParentTypes['WorkflowState'],
> = {
  actions?: Resolver<Maybe<Array<ResolversTypes['WorkflowStateAction']>>, ParentType, ContextType>;
  dates?: Resolver<ResolversTypes['WorkflowStateDates'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  name?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  rule?: Resolver<ResolversTypes['WorkflowConfigStateRulePayout'], ParentType, ContextType>;
  status?: Resolver<ResolversTypes['WorkflowStateStatusEnum'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['WorkflowConfigStateTypeEnum'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowStateActionResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowStateAction'] = ResolversParentTypes['WorkflowStateAction'],
> = {
  actor?: Resolver<ResolversTypes['WorkflowStateActionActor'], ParentType, ContextType>;
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  status?: Resolver<ResolversTypes['WorkflowStateActionStatusEnum'], ParentType, ContextType>;
  type?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowStateActionActorResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowStateActionActor'] = ResolversParentTypes['WorkflowStateActionActor'],
> = {
  comment?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  id?: Resolver<ResolversTypes['ID'], ParentType, ContextType>;
  key?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  meta?: Resolver<Maybe<ResolversTypes['JSONObject']>, ParentType, ContextType>;
  type?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  value?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type WorkflowStateDatesResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['WorkflowStateDates'] = ResolversParentTypes['WorkflowStateDates'],
> = {
  createdAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  updatedAt?: Resolver<ResolversTypes['DateTime'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type MerchantConfigurationUpdateResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['merchantConfigurationUpdateResponse'] = ResolversParentTypes['merchantConfigurationUpdateResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<ResolversTypes['String'], ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type UserOtpResponseResolvers<
  ContextType = any,
  ParentType extends ResolversParentTypes['userOtpResponse'] = ResolversParentTypes['userOtpResponse'],
> = {
  code?: Resolver<ResolversTypes['PositiveInt'], ParentType, ContextType>;
  message?: Resolver<Maybe<ResolversTypes['String']>, ParentType, ContextType>;
  success?: Resolver<ResolversTypes['Boolean'], ParentType, ContextType>;
  __isTypeOf?: IsTypeOfResolverFn<ParentType, ContextType>;
};

export type Resolvers<ContextType = any> = {
  AadhaarCaptchaResponse?: AadhaarCaptchaResponseResolvers<ContextType>;
  AadhaarCaptchaV2FailureResponse?: AadhaarCaptchaV2FailureResponseResolvers<ContextType>;
  AadhaarCaptchaV2Response?: AadhaarCaptchaV2ResponseResolvers<ContextType>;
  AadhaarCaptchaV2SuccessResponse?: AadhaarCaptchaV2SuccessResponseResolvers<ContextType>;
  AadhaarCaptchaVerifyResponse?: AadhaarCaptchaVerifyResponseResolvers<ContextType>;
  AadhaarDigilockerOtpFailureResponse?: AadhaarDigilockerOtpFailureResponseResolvers<ContextType>;
  AadhaarDigilockerOtpResponse?: AadhaarDigilockerOtpResponseResolvers<ContextType>;
  AadhaarDigilockerOtpSuccessResponse?: AadhaarDigilockerOtpSuccessResponseResolvers<ContextType>;
  AadhaarDigilockerOtpVerifyFailureResponse?: AadhaarDigilockerOtpVerifyFailureResponseResolvers<ContextType>;
  AadhaarDigilockerOtpVerifyResponse?: AadhaarDigilockerOtpVerifyResponseResolvers<ContextType>;
  AadhaarDigilockerOtpVerifySuccessResponse?: AadhaarDigilockerOtpVerifySuccessResponseResolvers<ContextType>;
  AadhaarDigilockerRedirectionUrlFailureResponse?: AadhaarDigilockerRedirectionUrlFailureResponseResolvers<ContextType>;
  AadhaarDigilockerRedirectionUrlResponse?: AadhaarDigilockerRedirectionUrlResponseResolvers<ContextType>;
  AadhaarDigilockerRedirectionUrlSuccessResponse?: AadhaarDigilockerRedirectionUrlSuccessResponseResolvers<ContextType>;
  AadhaarDigilockerRedirectionUrlVerifyResponse?: AadhaarDigilockerRedirectionUrlVerifyResponseResolvers<ContextType>;
  AadhaarDigilockerRedirectionVerificationFailureResponse?: AadhaarDigilockerRedirectionVerificationFailureResponseResolvers<ContextType>;
  AadhaarDigilockerRedirectionVerificationSuccessResponse?: AadhaarDigilockerRedirectionVerificationSuccessResponseResolvers<ContextType>;
  AadhaarOtpVerifyResponse?: AadhaarOtpVerifyResponseResolvers<ContextType>;
  AcceptPaymentsProduct?: AcceptPaymentsProductResolvers<ContextType>;
  AcceptPaymentsWidget?: AcceptPaymentsWidgetResolvers<ContextType>;
  AccountVerificationOtpResendResponse?: AccountVerificationOtpResendResponseResolvers<ContextType>;
  AccountVerificationOtpResponse?: AccountVerificationOtpResponseResolvers<ContextType>;
  AcquirerData?: AcquirerDataResolvers<ContextType>;
  Address?: AddressResolvers<ContextType>;
  AddressByPincodeFailureResponse?: AddressByPincodeFailureResponseResolvers<ContextType>;
  AddressByPincodeResponse?: AddressByPincodeResponseResolvers<ContextType>;
  AddressByPincodeSuccessResponse?: AddressByPincodeSuccessResponseResolvers<ContextType>;
  AggregationResultType?: AggregationResultTypeResolvers<ContextType>;
  ApproveIciciPayoutResponse?: ApproveIciciPayoutResponseResolvers<ContextType>;
  ApprovePayoutBatchResponse?: ApprovePayoutBatchResponseResolvers<ContextType>;
  ApprovePayoutBatchResponseFailure?: ApprovePayoutBatchResponseFailureResolvers<ContextType>;
  ApprovePayoutBatchResponseSuccess?: ApprovePayoutBatchResponseSuccessResolvers<ContextType>;
  ApprovePayoutResponse?: ApprovePayoutResponseResolvers<ContextType>;
  Auth?: AuthResolvers<ContextType>;
  AuthUnauthenticated?: AuthUnauthenticatedResolvers<ContextType>;
  AuthUnregistered?: AuthUnregisteredResolvers<ContextType>;
  AuthUser?: AuthUserResolvers<ContextType>;
  Bank?: BankResolvers<ContextType>;
  BigInt?: GraphQLScalarType;
  BusinessType?: BusinessTypeResolvers<ContextType>;
  CheckoutOptions?: CheckoutOptionsResolvers<ContextType>;
  ClarificationComment?: ClarificationCommentResolvers<ContextType>;
  ClarificationComments?: ClarificationCommentsResolvers<ContextType>;
  ConfigData?: ConfigDataResolvers<ContextType>;
  CouponApplyResponse?: CouponApplyResponseResolvers<ContextType>;
  CouponValidateResponse?: CouponValidateResponseResolvers<ContextType>;
  Currency?: CurrencyResolvers<ContextType>;
  Customer?: CustomerResolvers<ContextType>;
  CustomerAddress?: CustomerAddressResolvers<ContextType>;
  DateTime?: GraphQLScalarType;
  DeregisterFCMTokenResponse?: DeregisterFcmTokenResponseResolvers<ContextType>;
  EmailAddress?: GraphQLScalarType;
  FailedPaymentsOverviewFailureResponse?: FailedPaymentsOverviewFailureResponseResolvers<ContextType>;
  FailedPaymentsOverviewResponse?: FailedPaymentsOverviewResponseResolvers<ContextType>;
  FailedPaymentsOverviewSuccessResponse?: FailedPaymentsOverviewSuccessResponseResolvers<ContextType>;
  GoalTrackerMetaData?: GoalTrackerMetaDataResolvers<ContextType>;
  GoalTrackerSettings?: GoalTrackerSettingsResolvers<ContextType>;
  Image?: ImageResolvers<ContextType>;
  Invoice?: InvoiceResolvers<ContextType>;
  InvoiceAmount?: InvoiceAmountResolvers<ContextType>;
  InvoiceDate?: InvoiceDateResolvers<ContextType>;
  InvoiceItem?: InvoiceItemResolvers<ContextType>;
  InvoicesResponse?: InvoicesResponseResolvers<ContextType>;
  JSON?: GraphQLScalarType;
  JSONObject?: GraphQLScalarType;
  LoginOtpError?: LoginOtpErrorResolvers<ContextType>;
  LoginOtpResendError?: LoginOtpResendErrorResolvers<ContextType>;
  LoginOtpResendResponse?: LoginOtpResendResponseResolvers<ContextType>;
  LoginOtpResendSuccess?: LoginOtpResendSuccessResolvers<ContextType>;
  LoginOtpResponse?: LoginOtpResponseResolvers<ContextType>;
  LoginOtpSuccess?: LoginOtpSuccessResolvers<ContextType>;
  Merchant?: MerchantResolvers<ContextType>;
  MerchantAcceptanceChannel?: MerchantAcceptanceChannelResolvers<ContextType>;
  MerchantAcceptanceChannelWhatsappSmsEmail?: MerchantAcceptanceChannelWhatsappSmsEmailResolvers<ContextType>;
  MerchantActivation?: MerchantActivationResolvers<ContextType>;
  MerchantActivationDedupe?: MerchantActivationDedupeResolvers<ContextType>;
  MerchantActivationEscalationsBreached?: MerchantActivationEscalationsBreachedResolvers<ContextType>;
  MerchantActivationEscalationsNotBreached?: MerchantActivationEscalationsNotBreachedResolvers<ContextType>;
  MerchantActivationFlow?: MerchantActivationFlowResolvers<ContextType>;
  MerchantActivationResponse?: MerchantActivationResponseResolvers<ContextType>;
  MerchantAddress?: MerchantAddressResolvers<ContextType>;
  MerchantAnalytics?: MerchantAnalyticsResolvers<ContextType>;
  MerchantApiKey?: MerchantApiKeyResolvers<ContextType>;
  MerchantApiKeyCreateResponse?: MerchantApiKeyCreateResponseResolvers<ContextType>;
  MerchantApiKeyInterface?: MerchantApiKeyInterfaceResolvers<ContextType>;
  MerchantApiKeyRegenerateNew?: MerchantApiKeyRegenerateNewResolvers<ContextType>;
  MerchantApiKeyRegenerateOld?: MerchantApiKeyRegenerateOldResolvers<ContextType>;
  MerchantApiKeyRegenerateResponse?: MerchantApiKeyRegenerateResponseResolvers<ContextType>;
  MerchantApiKeysCreateFailure?: MerchantApiKeysCreateFailureResolvers<ContextType>;
  MerchantApiKeysCreateResponse?: MerchantApiKeysCreateResponseResolvers<ContextType>;
  MerchantApiKeysCreateSuccess?: MerchantApiKeysCreateSuccessResolvers<ContextType>;
  MerchantAverageOrderField?: MerchantAverageOrderFieldResolvers<ContextType>;
  MerchantAverageOrderFieldValue?: MerchantAverageOrderFieldValueResolvers<ContextType>;
  MerchantBalance?: MerchantBalanceResolvers<ContextType>;
  MerchantBank?: MerchantBankResolvers<ContextType>;
  MerchantBankAccountDetails?: MerchantBankAccountDetailsResolvers<ContextType>;
  MerchantBankAccountDocumentUploadFailureResponse?: MerchantBankAccountDocumentUploadFailureResponseResolvers<ContextType>;
  MerchantBankAccountDocumentUploadResponse?: MerchantBankAccountDocumentUploadResponseResolvers<ContextType>;
  MerchantBankAccountDocumentUploadSuccessResponse?: MerchantBankAccountDocumentUploadSuccessResponseResolvers<ContextType>;
  MerchantBankAccountUpdateFailureResponse?: MerchantBankAccountUpdateFailureResponseResolvers<ContextType>;
  MerchantBankAccountUpdateResponse?: MerchantBankAccountUpdateResponseResolvers<ContextType>;
  MerchantBankAccountUpdateSuccessResponse?: MerchantBankAccountUpdateSuccessResponseResolvers<ContextType>;
  MerchantBankDetails?: MerchantBankDetailsResolvers<ContextType>;
  MerchantBankDetailsFailureResponse?: MerchantBankDetailsFailureResponseResolvers<ContextType>;
  MerchantBankDetailsResponse?: MerchantBankDetailsResponseResolvers<ContextType>;
  MerchantBankDetailsSuccessResponse?: MerchantBankDetailsSuccessResponseResolvers<ContextType>;
  MerchantBankingAccount?: MerchantBankingAccountResolvers<ContextType>;
  MerchantBankingAccountBalance?: MerchantBankingAccountBalanceResolvers<ContextType>;
  MerchantBankingAccountsBalanceResponse?: MerchantBankingAccountsBalanceResponseResolvers<ContextType>;
  MerchantBankingRole?: MerchantBankingRoleResolvers<ContextType>;
  MerchantBusiness?: MerchantBusinessResolvers<ContextType>;
  MerchantBusinessAddress?: MerchantBusinessAddressResolvers<ContextType>;
  MerchantBusinessAppDetailsResponse?: MerchantBusinessAppDetailsResponseResolvers<ContextType>;
  MerchantBusinessCategoriesResponse?: MerchantBusinessCategoriesResponseResolvers<ContextType>;
  MerchantBusinessParentCategory?: MerchantBusinessParentCategoryResolvers<ContextType>;
  MerchantBusinessSubCategory?: MerchantBusinessSubCategoryResolvers<ContextType>;
  MerchantBusinessTypeField?: MerchantBusinessTypeFieldResolvers<ContextType>;
  MerchantBusinessTypesResponse?: MerchantBusinessTypesResponseResolvers<ContextType>;
  MerchantBusinessWebsiteDetailsResponse?: MerchantBusinessWebsiteDetailsResponseResolvers<ContextType>;
  MerchantClarificationDetail?: MerchantClarificationDetailResolvers<ContextType>;
  MerchantClarificationDetailsResponse?: MerchantClarificationDetailsResponseResolvers<ContextType>;
  MerchantClarificationDetailsSubmitResponse?: MerchantClarificationDetailsSubmitResponseResolvers<ContextType>;
  MerchantClarificationDetailsUpdateResponse?: MerchantClarificationDetailsUpdateResponseResolvers<ContextType>;
  MerchantClarificationFieldValues?: MerchantClarificationFieldValuesResolvers<ContextType>;
  MerchantClarifications?: MerchantClarificationsResolvers<ContextType>;
  MerchantConfig?: MerchantConfigResolvers<ContextType>;
  MerchantConfigFailure?: MerchantConfigFailureResolvers<ContextType>;
  MerchantConfigUpdateResponse?: MerchantConfigUpdateResponseResolvers<ContextType>;
  MerchantConfiguration?: MerchantConfigurationResolvers<ContextType>;
  MerchantConsentFailure?: MerchantConsentFailureResolvers<ContextType>;
  MerchantConsentResponse?: MerchantConsentResponseResolvers<ContextType>;
  MerchantConsentSuccess?: MerchantConsentSuccessResolvers<ContextType>;
  MerchantConsentsFailureResponse?: MerchantConsentsFailureResponseResolvers<ContextType>;
  MerchantConsentsResponse?: MerchantConsentsResponseResolvers<ContextType>;
  MerchantConsentsSuccessResponse?: MerchantConsentsSuccessResponseResolvers<ContextType>;
  MerchantContact?: MerchantContactResolvers<ContextType>;
  MerchantContactCreateResponse?: MerchantContactCreateResponseResolvers<ContextType>;
  MerchantContactEmailOtpSendFailureResponse?: MerchantContactEmailOtpSendFailureResponseResolvers<ContextType>;
  MerchantContactEmailOtpSendResponse?: MerchantContactEmailOtpSendResponseResolvers<ContextType>;
  MerchantContactEmailOtpSendSuccessResponse?: MerchantContactEmailOtpSendSuccessResponseResolvers<ContextType>;
  MerchantContactFundAccount?: MerchantContactFundAccountResolvers<ContextType>;
  MerchantContactFundAccountCreateResponse?: MerchantContactFundAccountCreateResponseResolvers<ContextType>;
  MerchantContactFundAccountDetails?: MerchantContactFundAccountDetailsResolvers<ContextType>;
  MerchantContactFundAccountDetailsBankAccount?: MerchantContactFundAccountDetailsBankAccountResolvers<ContextType>;
  MerchantContactFundAccountDetailsCard?: MerchantContactFundAccountDetailsCardResolvers<ContextType>;
  MerchantContactFundAccountDetailsVPA?: MerchantContactFundAccountDetailsVpaResolvers<ContextType>;
  MerchantContactFundAccountDetailsWallet?: MerchantContactFundAccountDetailsWalletResolvers<ContextType>;
  MerchantContactFundAccountsResponse?: MerchantContactFundAccountsResponseResolvers<ContextType>;
  MerchantContactPerson?: MerchantContactPersonResolvers<ContextType>;
  MerchantContactTypeCreateResponse?: MerchantContactTypeCreateResponseResolvers<ContextType>;
  MerchantContactTypeCreateResponseDuplicate?: MerchantContactTypeCreateResponseDuplicateResolvers<ContextType>;
  MerchantContactTypeCreateResponseSuccess?: MerchantContactTypeCreateResponseSuccessResolvers<ContextType>;
  MerchantContactUpdateResponse?: MerchantContactUpdateResponseResolvers<ContextType>;
  MerchantContactsResponse?: MerchantContactsResponseResolvers<ContextType>;
  MerchantCreditBalance?: MerchantCreditBalanceResolvers<ContextType>;
  MerchantCreditBalanceFailureResponse?: MerchantCreditBalanceFailureResponseResolvers<ContextType>;
  MerchantCreditBalanceResponse?: MerchantCreditBalanceResponseResolvers<ContextType>;
  MerchantCreditBalanceSuccessResponse?: MerchantCreditBalanceSuccessResponseResolvers<ContextType>;
  MerchantDocument?: MerchantDocumentResolvers<ContextType>;
  MerchantDocumentByIdResponse?: MerchantDocumentByIdResponseResolvers<ContextType>;
  MerchantDocumentField?: MerchantDocumentFieldResolvers<ContextType>;
  MerchantDocumentFieldValue?: MerchantDocumentFieldValueResolvers<ContextType>;
  MerchantDocumentFieldValueInterface?: MerchantDocumentFieldValueInterfaceResolvers<ContextType>;
  MerchantDocumentUpload?: MerchantDocumentUploadResolvers<ContextType>;
  MerchantDocumentUploadFailureResponse?: MerchantDocumentUploadFailureResponseResolvers<ContextType>;
  MerchantDocumentUploadResponse?: MerchantDocumentUploadResponseResolvers<ContextType>;
  MerchantDocumentUploadSuccessResponse?: MerchantDocumentUploadSuccessResponseResolvers<ContextType>;
  MerchantEmailField?: MerchantEmailFieldResolvers<ContextType>;
  MerchantEmailUpdateFailureResponse?: MerchantEmailUpdateFailureResponseResolvers<ContextType>;
  MerchantEmailUpdateResponse?: MerchantEmailUpdateResponseResolvers<ContextType>;
  MerchantEmailUpdateSuccessResponse?: MerchantEmailUpdateSuccessResponseResolvers<ContextType>;
  MerchantEscalationAction?: MerchantEscalationActionResolvers<ContextType>;
  MerchantEscalationLimit?: MerchantEscalationLimitResolvers<ContextType>;
  MerchantEscalations?: MerchantEscalationsResolvers<ContextType>;
  MerchantFeatureFlag?: MerchantFeatureFlagResolvers<ContextType>;
  MerchantFeeBasedGating?: MerchantFeeBasedGatingResolvers<ContextType>;
  MerchantFieldClarificationReason?: MerchantFieldClarificationReasonResolvers<ContextType>;
  MerchantFieldInterface?: MerchantFieldInterfaceResolvers<ContextType>;
  MerchantGstinUpdate?: MerchantGstinUpdateResolvers<ContextType>;
  MerchantGstinUpdateAsyncFlowSuccessResponse?: MerchantGstinUpdateAsyncFlowSuccessResponseResolvers<ContextType>;
  MerchantGstinUpdateFailureResponse?: MerchantGstinUpdateFailureResponseResolvers<ContextType>;
  MerchantGstinUpdateInSyncFlowResponse?: MerchantGstinUpdateInSyncFlowResponseResolvers<ContextType>;
  MerchantGstinUpdateInSyncWorkFlowCreatedResponse?: MerchantGstinUpdateInSyncWorkFlowCreatedResponseResolvers<ContextType>;
  MerchantGstinUpdateResponse?: MerchantGstinUpdateResponseResolvers<ContextType>;
  MerchantIdentityResponse?: MerchantIdentityResponseResolvers<ContextType>;
  MerchantKYCPartnerAccessResponse?: MerchantKycPartnerAccessResponseResolvers<ContextType>;
  MerchantKYCPartnerAccessStatusUpdateResponse?: MerchantKycPartnerAccessStatusUpdateResponseResolvers<ContextType>;
  MerchantKYCPartnerAccessUpdateFailureResponse?: MerchantKycPartnerAccessUpdateFailureResponseResolvers<ContextType>;
  MerchantKYCPartnerAccessUpdateSuccessResponse?: MerchantKycPartnerAccessUpdateSuccessResponseResolvers<ContextType>;
  MerchantName?: MerchantNameResolvers<ContextType>;
  MerchantNcEligibilityResponse?: MerchantNcEligibilityResponseResolvers<ContextType>;
  MerchantNumberField?: MerchantNumberFieldResolvers<ContextType>;
  MerchantOnboardingConfig?: MerchantOnboardingConfigResolvers<ContextType>;
  MerchantOnboardingQuestionDetail?: MerchantOnboardingQuestionDetailResolvers<ContextType>;
  MerchantOnboardingQuestionDetailsFailureResponse?: MerchantOnboardingQuestionDetailsFailureResponseResolvers<ContextType>;
  MerchantOnboardingQuestionDetailsResponse?: MerchantOnboardingQuestionDetailsResponseResolvers<ContextType>;
  MerchantOnboardingQuestionDetailsSuccessResponse?: MerchantOnboardingQuestionDetailsSuccessResponseResolvers<ContextType>;
  MerchantOnboardingQuestionDetailsUpdateResponse?: MerchantOnboardingQuestionDetailsUpdateResponseResolvers<ContextType>;
  MerchantPaymentAcceptanceChannels?: MerchantPaymentAcceptanceChannelsResolvers<ContextType>;
  MerchantPaymentHandle?: MerchantPaymentHandleResolvers<ContextType>;
  MerchantPaymentHandleAvailabilityFailureResponse?: MerchantPaymentHandleAvailabilityFailureResponseResolvers<ContextType>;
  MerchantPaymentHandleAvailabilityResponse?: MerchantPaymentHandleAvailabilityResponseResolvers<ContextType>;
  MerchantPaymentHandleAvailabilitySuccessResponse?: MerchantPaymentHandleAvailabilitySuccessResponseResolvers<ContextType>;
  MerchantPaymentHandleCreateFailureResponse?: MerchantPaymentHandleCreateFailureResponseResolvers<ContextType>;
  MerchantPaymentHandleCreateResponse?: MerchantPaymentHandleCreateResponseResolvers<ContextType>;
  MerchantPaymentHandleCreateSuccessResponse?: MerchantPaymentHandleCreateSuccessResponseResolvers<ContextType>;
  MerchantPaymentHandleEncryptedAmountFailureResponse?: MerchantPaymentHandleEncryptedAmountFailureResponseResolvers<ContextType>;
  MerchantPaymentHandleEncryptedAmountResponse?: MerchantPaymentHandleEncryptedAmountResponseResolvers<ContextType>;
  MerchantPaymentHandleEncryptedAmountSuccessResponse?: MerchantPaymentHandleEncryptedAmountSuccessResponseResolvers<ContextType>;
  MerchantPaymentHandleFailureResponse?: MerchantPaymentHandleFailureResponseResolvers<ContextType>;
  MerchantPaymentHandleResponse?: MerchantPaymentHandleResponseResolvers<ContextType>;
  MerchantPaymentHandleSuccessResponse?: MerchantPaymentHandleSuccessResponseResolvers<ContextType>;
  MerchantPaymentHandleSuggestionsResponse?: MerchantPaymentHandleSuggestionsResponseResolvers<ContextType>;
  MerchantPaymentHandleUpdateFailureResponse?: MerchantPaymentHandleUpdateFailureResponseResolvers<ContextType>;
  MerchantPaymentHandleUpdateResponse?: MerchantPaymentHandleUpdateResponseResolvers<ContextType>;
  MerchantPaymentHandleUpdateSuccessResponse?: MerchantPaymentHandleUpdateSuccessResponseResolvers<ContextType>;
  MerchantPhoneField?: MerchantPhoneFieldResolvers<ContextType>;
  MerchantPolicyEmptyPreviewResponse?: MerchantPolicyEmptyPreviewResponseResolvers<ContextType>;
  MerchantPolicyEmptyResponse?: MerchantPolicyEmptyResponseResolvers<ContextType>;
  MerchantPolicyEmptyV2PreviewResponse?: MerchantPolicyEmptyV2PreviewResponseResolvers<ContextType>;
  MerchantPolicyFailureResponse?: MerchantPolicyFailureResponseResolvers<ContextType>;
  MerchantPolicyPreview?: MerchantPolicyPreviewResolvers<ContextType>;
  MerchantPolicyPreviewFailureResponse?: MerchantPolicyPreviewFailureResponseResolvers<ContextType>;
  MerchantPolicyPreviewResponse?: MerchantPolicyPreviewResponseResolvers<ContextType>;
  MerchantPolicyPreviewSuccessResponse?: MerchantPolicyPreviewSuccessResponseResolvers<ContextType>;
  MerchantPolicyPreviewV2FailureResponse?: MerchantPolicyPreviewV2FailureResponseResolvers<ContextType>;
  MerchantPolicyPreviewV2Response?: MerchantPolicyPreviewV2ResponseResolvers<ContextType>;
  MerchantPolicyPreviewV2SuccessResponse?: MerchantPolicyPreviewV2SuccessResponseResolvers<ContextType>;
  MerchantPolicyPublishFailureResponse?: MerchantPolicyPublishFailureResponseResolvers<ContextType>;
  MerchantPolicyPublishResponse?: MerchantPolicyPublishResponseResolvers<ContextType>;
  MerchantPolicyPublishSuccessResponse?: MerchantPolicyPublishSuccessResponseResolvers<ContextType>;
  MerchantPolicyResponse?: MerchantPolicyResponseResolvers<ContextType>;
  MerchantPolicySuccessResponse?: MerchantPolicySuccessResponseResolvers<ContextType>;
  MerchantPolicyWizardV2EligibilityResponse?: MerchantPolicyWizardV2EligibilityResponseResolvers<ContextType>;
  MerchantPreference?: MerchantPreferenceResolvers<ContextType>;
  MerchantReferralFailureResponse?: MerchantReferralFailureResponseResolvers<ContextType>;
  MerchantReferralResponse?: MerchantReferralResponseResolvers<ContextType>;
  MerchantReferralSuccessResponse?: MerchantReferralSuccessResponseResolvers<ContextType>;
  MerchantSelfServeWorkflow?: MerchantSelfServeWorkflowResolvers<ContextType>;
  MerchantSelfServeWorkflowStatusFailureResponse?: MerchantSelfServeWorkflowStatusFailureResponseResolvers<ContextType>;
  MerchantSelfServeWorkflowStatusResponse?: MerchantSelfServeWorkflowStatusResponseResolvers<ContextType>;
  MerchantSelfServeWorkflowStatusSuccessResponse?: MerchantSelfServeWorkflowStatusSuccessResponseResolvers<ContextType>;
  MerchantSettlementConfigFailureResponse?: MerchantSettlementConfigFailureResponseResolvers<ContextType>;
  MerchantSettlementConfigResponse?: MerchantSettlementConfigResponseResolvers<ContextType>;
  MerchantSettlementConfigSuccessResponse?: MerchantSettlementConfigSuccessResponseResolvers<ContextType>;
  MerchantShopEstablishment?: MerchantShopEstablishmentResolvers<ContextType>;
  MerchantSocialMediaURLField?: MerchantSocialMediaUrlFieldResolvers<ContextType>;
  MerchantStakeholder?: MerchantStakeholderResolvers<ContextType>;
  MerchantStringField?: MerchantStringFieldResolvers<ContextType>;
  MerchantSupportDetails?: MerchantSupportDetailsResolvers<ContextType>;
  MerchantSupportDetailsFailureResponse?: MerchantSupportDetailsFailureResponseResolvers<ContextType>;
  MerchantSupportDetailsResponse?: MerchantSupportDetailsResponseResolvers<ContextType>;
  MerchantSupportDetailsSuccessResponse?: MerchantSupportDetailsSuccessResponseResolvers<ContextType>;
  MerchantSwitchResponse?: MerchantSwitchResponseResolvers<ContextType>;
  MerchantTransactionLimit?: MerchantTransactionLimitResolvers<ContextType>;
  MerchantURLField?: MerchantUrlFieldResolvers<ContextType>;
  MerchantValidateSocialMediaURLResponse?: MerchantValidateSocialMediaUrlResponseResolvers<ContextType>;
  MerchantVirtualAccount?: MerchantVirtualAccountResolvers<ContextType>;
  MerchantVirtualAccountReceiver?: MerchantVirtualAccountReceiverResolvers<ContextType>;
  MerchantVirtualAccountsResponse?: MerchantVirtualAccountsResponseResolvers<ContextType>;
  MerchantWebsite?: MerchantWebsiteResolvers<ContextType>;
  MerchantWebsiteAdditionalData?: MerchantWebsiteAdditionalDataResolvers<ContextType>;
  MerchantWebsiteApplicationDetail?: MerchantWebsiteApplicationDetailResolvers<ContextType>;
  MerchantWebsiteDetailsFailureResponse?: MerchantWebsiteDetailsFailureResponseResolvers<ContextType>;
  MerchantWebsiteDetailsResponse?: MerchantWebsiteDetailsResponseResolvers<ContextType>;
  MerchantWebsiteDocumentDeleteFailureResponse?: MerchantWebsiteDocumentDeleteFailureResponseResolvers<ContextType>;
  MerchantWebsiteDocumentDeleteResponse?: MerchantWebsiteDocumentDeleteResponseResolvers<ContextType>;
  MerchantWebsiteDocumentDeleteSuccessResponse?: MerchantWebsiteDocumentDeleteSuccessResponseResolvers<ContextType>;
  MerchantWebsiteDocumentUploadFailureResponse?: MerchantWebsiteDocumentUploadFailureResponseResolvers<ContextType>;
  MerchantWebsiteDocumentUploadResponse?: MerchantWebsiteDocumentUploadResponseResolvers<ContextType>;
  MerchantWebsiteDocumentUploadSuccessResponse?: MerchantWebsiteDocumentUploadSuccessResponseResolvers<ContextType>;
  MerchantWebsitePublishFailureResponse?: MerchantWebsitePublishFailureResponseResolvers<ContextType>;
  MerchantWebsitePublishResponse?: MerchantWebsitePublishResponseResolvers<ContextType>;
  MerchantWebsitePublishSuccessResponse?: MerchantWebsitePublishSuccessResponseResolvers<ContextType>;
  MerchantWebsiteSection?: MerchantWebsiteSectionResolvers<ContextType>;
  MerchantWebsiteTermsAndConditions?: MerchantWebsiteTermsAndConditionsResolvers<ContextType>;
  MerchantWebsitesResponse?: MerchantWebsitesResponseResolvers<ContextType>;
  MerchantWorkflowClarificationSubmitFailureResponse?: MerchantWorkflowClarificationSubmitFailureResponseResolvers<ContextType>;
  MerchantWorkflowClarificationSubmitResponse?: MerchantWorkflowClarificationSubmitResponseResolvers<ContextType>;
  MerchantWorkflowClarificationSubmitSuccessResponse?: MerchantWorkflowClarificationSubmitSuccessResponseResolvers<ContextType>;
  Money?: MoneyResolvers<ContextType>;
  Mutation?: MutationResolvers<ContextType>;
  MutationResponseInterface?: MutationResponseInterfaceResolvers<ContextType>;
  NonNegativeInt?: GraphQLScalarType;
  NotificationEmailUpdateFailureResponse?: NotificationEmailUpdateFailureResponseResolvers<ContextType>;
  NotificationEmailUpdateResponse?: NotificationEmailUpdateResponseResolvers<ContextType>;
  NotificationEmailUpdateSuccessResponse?: NotificationEmailUpdateSuccessResponseResolvers<ContextType>;
  NotificationWhatsAppOptIn?: NotificationWhatsAppOptInResolvers<ContextType>;
  OauthTokenAppleWatchOtp?: OauthTokenAppleWatchOtpResolvers<ContextType>;
  OauthTokenAppleWatchResponse?: OauthTokenAppleWatchResponseResolvers<ContextType>;
  OauthTokenAppleWatchResponseError?: OauthTokenAppleWatchResponseErrorResolvers<ContextType>;
  OauthTokenAppleWatchResponseSuccess?: OauthTokenAppleWatchResponseSuccessResolvers<ContextType>;
  OnboardingPaymentOrderCreateFailureResponse?: OnboardingPaymentOrderCreateFailureResponseResolvers<ContextType>;
  OnboardingPaymentOrderCreateResponse?: OnboardingPaymentOrderCreateResponseResolvers<ContextType>;
  OnboardingPaymentOrderCreateSuccessResponse?: OnboardingPaymentOrderCreateSuccessResponseResolvers<ContextType>;
  OnboardingPaymentOrderVerifyResponse?: OnboardingPaymentOrderVerifyResponseResolvers<ContextType>;
  OnboardingWidget?: OnboardingWidgetResolvers<ContextType>;
  Order?: OrderResolvers<ContextType>;
  OrderAmount?: OrderAmountResolvers<ContextType>;
  OrderCreateFailureResponse?: OrderCreateFailureResponseResolvers<ContextType>;
  OrderCreateResponse?: OrderCreateResponseResolvers<ContextType>;
  OrderCreateSuccessResponse?: OrderCreateSuccessResponseResolvers<ContextType>;
  OrderDate?: OrderDateResolvers<ContextType>;
  Organisation?: OrganisationResolvers<ContextType>;
  OrganisationEmail?: OrganisationEmailResolvers<ContextType>;
  OrganisationLogo?: OrganisationLogoResolvers<ContextType>;
  OrganisationName?: OrganisationNameResolvers<ContextType>;
  OverViewResponseType?: OverViewResponseTypeResolvers<ContextType>;
  PPTrackingSettings?: PpTrackingSettingsResolvers<ContextType>;
  PageAcquirerData?: PageAcquirerDataResolvers<ContextType>;
  PageItem?: PageItemResolvers<ContextType>;
  PageItemTaxDetails?: PageItemTaxDetailsResolvers<ContextType>;
  PaginationResponseInterface?: PaginationResponseInterfaceResolvers<ContextType>;
  PartnerConfigFailure?: PartnerConfigFailureResolvers<ContextType>;
  PartnerConfigResponse?: PartnerConfigResponseResolvers<ContextType>;
  PartnerConfigSuccess?: PartnerConfigSuccessResolvers<ContextType>;
  PartnerWebhookSettings?: PartnerWebhookSettingsResolvers<ContextType>;
  Payment?: PaymentResolvers<ContextType>;
  PaymentAggregationSummary?: PaymentAggregationSummaryResolvers<ContextType>;
  PaymentAmount?: PaymentAmountResolvers<ContextType>;
  PaymentAnalytics?: PaymentAnalyticsResolvers<ContextType>;
  PaymentAnalyticsResponse?: PaymentAnalyticsResponseResolvers<ContextType>;
  PaymentAnalyticsWidget?: PaymentAnalyticsWidgetResolvers<ContextType>;
  PaymentCaptureResponse?: PaymentCaptureResponseResolvers<ContextType>;
  PaymentDetails?: PaymentDetailsResolvers<ContextType>;
  PaymentEmiDetails?: PaymentEmiDetailsResolvers<ContextType>;
  PaymentError?: PaymentErrorResolvers<ContextType>;
  PaymentHandleWidget?: PaymentHandleWidgetResolvers<ContextType>;
  PaymentInstantRefundEligibilityAmount?: PaymentInstantRefundEligibilityAmountResolvers<ContextType>;
  PaymentInstantRefundEligibilityResponse?: PaymentInstantRefundEligibilityResponseResolvers<ContextType>;
  PaymentLink?: PaymentLinkResolvers<ContextType>;
  PaymentLinkAmount?: PaymentLinkAmountResolvers<ContextType>;
  PaymentLinkCancelResponse?: PaymentLinkCancelResponseResolvers<ContextType>;
  PaymentLinkCreateResponse?: PaymentLinkCreateResponseResolvers<ContextType>;
  PaymentLinkDate?: PaymentLinkDateResolvers<ContextType>;
  PaymentLinkNotifyBy?: PaymentLinkNotifyByResolvers<ContextType>;
  PaymentLinkNotifyResponse?: PaymentLinkNotifyResponseResolvers<ContextType>;
  PaymentLinkReminder?: PaymentLinkReminderResolvers<ContextType>;
  PaymentLinksResponse?: PaymentLinksResponseResolvers<ContextType>;
  PaymentMethod?: PaymentMethodResolvers<ContextType>;
  PaymentMethodApp?: PaymentMethodAppResolvers<ContextType>;
  PaymentMethodBankTransfer?: PaymentMethodBankTransferResolvers<ContextType>;
  PaymentMethodCard?: PaymentMethodCardResolvers<ContextType>;
  PaymentMethodCardExpiry?: PaymentMethodCardExpiryResolvers<ContextType>;
  PaymentMethodCardlessEmi?: PaymentMethodCardlessEmiResolvers<ContextType>;
  PaymentMethodEmandate?: PaymentMethodEmandateResolvers<ContextType>;
  PaymentMethodEmi?: PaymentMethodEmiResolvers<ContextType>;
  PaymentMethodNetBanking?: PaymentMethodNetBankingResolvers<ContextType>;
  PaymentMethodPayLater?: PaymentMethodPayLaterResolvers<ContextType>;
  PaymentMethodUPITransfer?: PaymentMethodUpiTransferResolvers<ContextType>;
  PaymentMethodWallet?: PaymentMethodWalletResolvers<ContextType>;
  PaymentOverviewResponse?: PaymentOverviewResponseResolvers<ContextType>;
  PaymentPage?: PaymentPageResolvers<ContextType>;
  PaymentPageAmount?: PaymentPageAmountResolvers<ContextType>;
  PaymentPageDate?: PaymentPageDateResolvers<ContextType>;
  PaymentPageItem?: PaymentPageItemResolvers<ContextType>;
  PaymentPageSettings?: PaymentPageSettingsResolvers<ContextType>;
  PaymentPageSupportDetails?: PaymentPageSupportDetailsResolvers<ContextType>;
  PaymentPageTransaction?: PaymentPageTransactionResolvers<ContextType>;
  PaymentPageTransactionResponse?: PaymentPageTransactionResponseResolvers<ContextType>;
  PaymentPagesResponse?: PaymentPagesResponseResolvers<ContextType>;
  PaymentPayerBankAccount?: PaymentPayerBankAccountResolvers<ContextType>;
  PaymentRefund?: PaymentRefundResolvers<ContextType>;
  PaymentRefundResponse?: PaymentRefundResponseResolvers<ContextType>;
  PaymentRefundSpeed?: PaymentRefundSpeedResolvers<ContextType>;
  PaymentSummaryResponse?: PaymentSummaryResponseResolvers<ContextType>;
  PaymentTerm?: PaymentTermResolvers<ContextType>;
  PaymentVirtualAccount?: PaymentVirtualAccountResolvers<ContextType>;
  PaymentVirtualAccountAmount?: PaymentVirtualAccountAmountResolvers<ContextType>;
  PaymentVirtualAccountDates?: PaymentVirtualAccountDatesResolvers<ContextType>;
  PaymentsNewLaunchProductViewUpdate?: PaymentsNewLaunchProductViewUpdateResolvers<ContextType>;
  PaymentsProductFtuxUpdateResponse?: PaymentsProductFtuxUpdateResponseResolvers<ContextType>;
  PaymentsResponse?: PaymentsResponseResolvers<ContextType>;
  PaymentsWidgetError?: PaymentsWidgetErrorResolvers<ContextType>;
  PaymentsWidgets?: PaymentsWidgetsResolvers<ContextType>;
  Payout?: PayoutResolvers<ContextType>;
  PayoutApproveBulkResponse?: PayoutApproveBulkResponseResolvers<ContextType>;
  PayoutApproveBulkResponseFailure?: PayoutApproveBulkResponseFailureResolvers<ContextType>;
  PayoutApproveBulkResponseSuccess?: PayoutApproveBulkResponseSuccessResolvers<ContextType>;
  PayoutBatch?: PayoutBatchResolvers<ContextType>;
  PayoutBatchDates?: PayoutBatchDatesResolvers<ContextType>;
  PayoutBatchPayoutsCount?: PayoutBatchPayoutsCountResolvers<ContextType>;
  PayoutBatchesResponse?: PayoutBatchesResponseResolvers<ContextType>;
  PayoutCompositeCreateResponse?: PayoutCompositeCreateResponseResolvers<ContextType>;
  PayoutCreateIciciResponse?: PayoutCreateIciciResponseResolvers<ContextType>;
  PayoutCreateResponse?: PayoutCreateResponseResolvers<ContextType>;
  PayoutDate?: PayoutDateResolvers<ContextType>;
  PayoutFee?: PayoutFeeResolvers<ContextType>;
  PayoutLink?: PayoutLinkResolvers<ContextType>;
  PayoutLinkCreateResponse?: PayoutLinkCreateResponseResolvers<ContextType>;
  PayoutLinkDate?: PayoutLinkDateResolvers<ContextType>;
  PayoutLinkSentVia?: PayoutLinkSentViaResolvers<ContextType>;
  PayoutLinksResponse?: PayoutLinksResponseResolvers<ContextType>;
  PayoutPurpose?: PayoutPurposeResolvers<ContextType>;
  PayoutPurposeCreateResponse?: PayoutPurposeCreateResponseResolvers<ContextType>;
  PayoutRejectBulkResponse?: PayoutRejectBulkResponseResolvers<ContextType>;
  PayoutRejectBulkResponseFailure?: PayoutRejectBulkResponseFailureResolvers<ContextType>;
  PayoutRejectBulkResponseSuccess?: PayoutRejectBulkResponseSuccessResolvers<ContextType>;
  PayoutSource?: PayoutSourceResolvers<ContextType>;
  PayoutWorkflow?: PayoutWorkflowResolvers<ContextType>;
  PayoutWorkflowHistory?: PayoutWorkflowHistoryResolvers<ContextType>;
  PayoutWorkflowRole?: PayoutWorkflowRoleResolvers<ContextType>;
  PayoutWorkflowRoleChecker?: PayoutWorkflowRoleCheckerResolvers<ContextType>;
  PayoutWorkflowStep?: PayoutWorkflowStepResolvers<ContextType>;
  PayoutsPendingSummary?: PayoutsPendingSummaryResolvers<ContextType>;
  PayoutsQueuedSummary?: PayoutsQueuedSummaryResolvers<ContextType>;
  PayoutsQueuedSummaryBeneficiaryBankDown?: PayoutsQueuedSummaryBeneficiaryBankDownResolvers<ContextType>;
  PayoutsQueuedSummaryLowBalance?: PayoutsQueuedSummaryLowBalanceResolvers<ContextType>;
  PayoutsQueuedSummaryNEFTLimitExhausted?: PayoutsQueuedSummaryNeftLimitExhaustedResolvers<ContextType>;
  PayoutsQueuedSummaryNEFTWindowClosed?: PayoutsQueuedSummaryNeftWindowClosedResolvers<ContextType>;
  PayoutsQueuedSummaryNPCISystemDown?: PayoutsQueuedSummaryNpciSystemDownResolvers<ContextType>;
  PayoutsQueuedSummaryWithoutReason?: PayoutsQueuedSummaryWithoutReasonResolvers<ContextType>;
  PayoutsResponse?: PayoutsResponseResolvers<ContextType>;
  PayoutsScheduledSummary?: PayoutsScheduledSummaryResolvers<ContextType>;
  PayoutsScheduledSummaryAllTime?: PayoutsScheduledSummaryAllTimeResolvers<ContextType>;
  PayoutsScheduledSummaryNextMonth?: PayoutsScheduledSummaryNextMonthResolvers<ContextType>;
  PayoutsScheduledSummaryNextTwoDays?: PayoutsScheduledSummaryNextTwoDaysResolvers<ContextType>;
  PayoutsScheduledSummaryNextWeek?: PayoutsScheduledSummaryNextWeekResolvers<ContextType>;
  PayoutsScheduledSummaryToday?: PayoutsScheduledSummaryTodayResolvers<ContextType>;
  PayoutsSummary?: PayoutsSummaryResolvers<ContextType>;
  Phone?: PhoneResolvers<ContextType>;
  PointOfSale?: PointOfSaleResolvers<ContextType>;
  PointOfSaleKeyFetchResponse?: PointOfSaleKeyFetchResponseResolvers<ContextType>;
  PointOfSalePaymentCreateFailureResponse?: PointOfSalePaymentCreateFailureResponseResolvers<ContextType>;
  PointOfSalePaymentCreateResponse?: PointOfSalePaymentCreateResponseResolvers<ContextType>;
  PointOfSalePaymentCreateSuccessResponse?: PointOfSalePaymentCreateSuccessResponseResolvers<ContextType>;
  PointOfSalePaymentUpdateFailureResponse?: PointOfSalePaymentUpdateFailureResponseResolvers<ContextType>;
  PointOfSalePaymentUpdateResponse?: PointOfSalePaymentUpdateResponseResolvers<ContextType>;
  PointOfSalePaymentUpdateSuccessResponse?: PointOfSalePaymentUpdateSuccessResponseResolvers<ContextType>;
  PositiveInt?: GraphQLScalarType;
  QRCode?: QrCodeResolvers<ContextType>;
  QRCodeCreateFailureResponse?: QrCodeCreateFailureResponseResolvers<ContextType>;
  QRCodeCreateResponse?: QrCodeCreateResponseResolvers<ContextType>;
  QRCodeCreateSuccessResponse?: QrCodeCreateSuccessResponseResolvers<ContextType>;
  QRCodeDate?: QrCodeDateResolvers<ContextType>;
  QRCodePaymentDetail?: QrCodePaymentDetailResolvers<ContextType>;
  QRCodesResponse?: QrCodesResponseResolvers<ContextType>;
  Query?: QueryResolvers<ContextType>;
  RecentTransactionsWidget?: RecentTransactionsWidgetResolvers<ContextType>;
  RefreshAccessToken?: RefreshAccessTokenResolvers<ContextType>;
  RefundsResponse?: RefundsResponseResolvers<ContextType>;
  RegisterBusinessResponse?: RegisterBusinessResponseResolvers<ContextType>;
  RegisterEmail?: RegisterEmailResolvers<ContextType>;
  RegisterEmailError?: RegisterEmailErrorResolvers<ContextType>;
  RegisterEmailSuccess?: RegisterEmailSuccessResolvers<ContextType>;
  RegisterEmailVerifyResponse?: RegisterEmailVerifyResponseResolvers<ContextType>;
  RegisterEmailVerifyResponseFailure?: RegisterEmailVerifyResponseFailureResolvers<ContextType>;
  RegisterEmailVerifyResponseSuccess?: RegisterEmailVerifyResponseSuccessResolvers<ContextType>;
  RegisterFCMTokenResponse?: RegisterFcmTokenResponseResolvers<ContextType>;
  RegisterMerchantResponse?: RegisterMerchantResponseResolvers<ContextType>;
  RegisterMerchantResponseFailure?: RegisterMerchantResponseFailureResolvers<ContextType>;
  RegisterMerchantResponseSuccess?: RegisterMerchantResponseSuccessResolvers<ContextType>;
  RegisterMobileVerifyResponse?: RegisterMobileVerifyResponseResolvers<ContextType>;
  RegisterMobileVerifyResponseFailure?: RegisterMobileVerifyResponseFailureResolvers<ContextType>;
  RegisterMobileVerifyResponseSuccess?: RegisterMobileVerifyResponseSuccessResolvers<ContextType>;
  RegisterOAuth?: RegisterOAuthResolvers<ContextType>;
  RegisterOAuthEmailError?: RegisterOAuthEmailErrorResolvers<ContextType>;
  RegisterOAuthEmailExist?: RegisterOAuthEmailExistResolvers<ContextType>;
  RegisterOAuthInvalidTokenError?: RegisterOAuthInvalidTokenErrorResolvers<ContextType>;
  RejectPayoutBatchResponse?: RejectPayoutBatchResponseResolvers<ContextType>;
  RejectPayoutBatchResponseFailure?: RejectPayoutBatchResponseFailureResolvers<ContextType>;
  RejectPayoutBatchResponseSuccess?: RejectPayoutBatchResponseSuccessResolvers<ContextType>;
  RejectPayoutResponse?: RejectPayoutResponseResolvers<ContextType>;
  ResendEmailOtp?: ResendEmailOtpResolvers<ContextType>;
  ResendTwoFactorLoginOtpResponse?: ResendTwoFactorLoginOtpResponseResolvers<ContextType>;
  ResetPasswordEmail?: ResetPasswordEmailResolvers<ContextType>;
  SendApprovePayoutBatchOtp?: SendApprovePayoutBatchOtpResolvers<ContextType>;
  SendApprovePayoutOtp?: SendApprovePayoutOtpResolvers<ContextType>;
  SendCreatePayoutLinkOtp?: SendCreatePayoutLinkOtpResolvers<ContextType>;
  SendCreatePayoutOtp?: SendCreatePayoutOtpResolvers<ContextType>;
  SendIciciPayoutOtpResponse?: SendIciciPayoutOtpResponseResolvers<ContextType>;
  SendPayoutApproveBulkOtp?: SendPayoutApproveBulkOtpResolvers<ContextType>;
  SendPayoutCompositeOtp?: SendPayoutCompositeOtpResolvers<ContextType>;
  Settlement?: SettlementResolvers<ContextType>;
  SettlementAmount?: SettlementAmountResolvers<ContextType>;
  SettlementBreakup?: SettlementBreakupResolvers<ContextType>;
  SettlementCycle?: SettlementCycleResolvers<ContextType>;
  SettlementsResponse?: SettlementsResponseResolvers<ContextType>;
  SettlementsWidget?: SettlementsWidgetResolvers<ContextType>;
  SmsNotificationStatusResponse?: SmsNotificationStatusResponseResolvers<ContextType>;
  SmsNotificationToggle?: SmsNotificationToggleResolvers<ContextType>;
  TDSCategory?: TdsCategoryResolvers<ContextType>;
  Transaction?: TransactionResolvers<ContextType>;
  TransactionAmount?: TransactionAmountResolvers<ContextType>;
  TransactionError?: TransactionErrorResolvers<ContextType>;
  TransactionSource?: TransactionSourceResolvers<ContextType>;
  TransactionSourceAdjustment?: TransactionSourceAdjustmentResolvers<ContextType>;
  TransactionSourceBankTransfer?: TransactionSourceBankTransferResolvers<ContextType>;
  TransactionSourceBankTransferPayee?: TransactionSourceBankTransferPayeeResolvers<ContextType>;
  TransactionSourceBankTransferPayer?: TransactionSourceBankTransferPayerResolvers<ContextType>;
  TransactionSourceDetails?: TransactionSourceDetailsResolvers<ContextType>;
  TransactionSourceExternal?: TransactionSourceExternalResolvers<ContextType>;
  TransactionSourceFundAccountValidation?: TransactionSourceFundAccountValidationResolvers<ContextType>;
  TransactionSourceReversal?: TransactionSourceReversalResolvers<ContextType>;
  TransactionStatus?: TransactionStatusResolvers<ContextType>;
  TransactionsResponse?: TransactionsResponseResolvers<ContextType>;
  TwoFactorAddMobileOtpErrorResponse?: TwoFactorAddMobileOtpErrorResponseResolvers<ContextType>;
  TwoFactorAddMobileOtpResponse?: TwoFactorAddMobileOtpResponseResolvers<ContextType>;
  TwoFactorAddMobileOtpSuccessResponse?: TwoFactorAddMobileOtpSuccessResponseResolvers<ContextType>;
  TwoFactorAddMobileOtpVerifyErrorResponse?: TwoFactorAddMobileOtpVerifyErrorResponseResolvers<ContextType>;
  TwoFactorAddMobileOtpVerifyResponse?: TwoFactorAddMobileOtpVerifyResponseResolvers<ContextType>;
  TwoFactorAddMobileOtpVerifySuccessResponse?: TwoFactorAddMobileOtpVerifySuccessResponseResolvers<ContextType>;
  TwoFactorAuthUpdateFailureResponse?: TwoFactorAuthUpdateFailureResponseResolvers<ContextType>;
  TwoFactorAuthUpdateResponse?: TwoFactorAuthUpdateResponseResolvers<ContextType>;
  TwoFactorAuthUpdateSuccessResponse?: TwoFactorAuthUpdateSuccessResponseResolvers<ContextType>;
  TwoFactorEmailOtpVerifyFailureResponse?: TwoFactorEmailOtpVerifyFailureResponseResolvers<ContextType>;
  TwoFactorEmailOtpVerifyResponse?: TwoFactorEmailOtpVerifyResponseResolvers<ContextType>;
  TwoFactorEmailOtpVerifySuccessResponse?: TwoFactorEmailOtpVerifySuccessResponseResolvers<ContextType>;
  TwoFactorOtpFailureResponse?: TwoFactorOtpFailureResponseResolvers<ContextType>;
  TwoFactorOtpResponse?: TwoFactorOtpResponseResolvers<ContextType>;
  TwoFactorOtpSuccessResponse?: TwoFactorOtpSuccessResponseResolvers<ContextType>;
  TwoFactorPasswordCreateErrorResponse?: TwoFactorPasswordCreateErrorResponseResolvers<ContextType>;
  TwoFactorPasswordCreateResponse?: TwoFactorPasswordCreateResponseResolvers<ContextType>;
  TwoFactorPasswordCreateSuccessResponse?: TwoFactorPasswordCreateSuccessResponseResolvers<ContextType>;
  TwoFactorPasswordEnabledErrorResponse?: TwoFactorPasswordEnabledErrorResponseResolvers<ContextType>;
  TwoFactorPasswordEnabledResponse?: TwoFactorPasswordEnabledResponseResolvers<ContextType>;
  TwoFactorPasswordEnabledSuccessResponse?: TwoFactorPasswordEnabledSuccessResponseResolvers<ContextType>;
  TwoFactorUnverifiedMobileVerifyResponse?: TwoFactorUnverifiedMobileVerifyResponseResolvers<ContextType>;
  URL?: GraphQLScalarType;
  UpdateMerchantConsentResponse?: UpdateMerchantConsentResponseResolvers<ContextType>;
  Upload?: GraphQLScalarType;
  User?: UserResolvers<ContextType>;
  UserAuthentication?: UserAuthenticationResolvers<ContextType>;
  UserContactDetails?: UserContactDetailsResolvers<ContextType>;
  UserContactDetailsUpdateResponse?: UserContactDetailsUpdateResponseResolvers<ContextType>;
  UserDeviceAnalyticsResponse?: UserDeviceAnalyticsResponseResolvers<ContextType>;
  UserLogout?: UserLogoutResolvers<ContextType>;
  UserOtpVerifyResponse?: UserOtpVerifyResponseResolvers<ContextType>;
  UserRole?: UserRoleResolvers<ContextType>;
  VPA?: GraphQLScalarType;
  ValidateVpaFailureResponse?: ValidateVpaFailureResponseResolvers<ContextType>;
  ValidateVpaResponse?: ValidateVpaResponseResolvers<ContextType>;
  ValidateVpaSuccessResponse?: ValidateVpaSuccessResponseResolvers<ContextType>;
  VendorPayment?: VendorPaymentResolvers<ContextType>;
  VendorPaymentCancelResponse?: VendorPaymentCancelResponseResolvers<ContextType>;
  VendorPaymentDates?: VendorPaymentDatesResolvers<ContextType>;
  VendorPaymentGST?: VendorPaymentGstResolvers<ContextType>;
  VendorPaymentInvoice?: VendorPaymentInvoiceResolvers<ContextType>;
  VendorPaymentInvoiceAttachment?: VendorPaymentInvoiceAttachmentResolvers<ContextType>;
  VendorPaymentPayoutAmounts?: VendorPaymentPayoutAmountsResolvers<ContextType>;
  VendorPaymentPayoutCreateResponse?: VendorPaymentPayoutCreateResponseResolvers<ContextType>;
  VendorPaymentTDS?: VendorPaymentTdsResolvers<ContextType>;
  VendorPaymentsResponse?: VendorPaymentsResponseResolvers<ContextType>;
  WhatsappNotificationStatusResponse?: WhatsappNotificationStatusResponseResolvers<ContextType>;
  WhatsappNotificationToggle?: WhatsappNotificationToggleResolvers<ContextType>;
  Widget?: WidgetResolvers<ContextType>;
  Workflow?: WorkflowResolvers<ContextType>;
  WorkflowConfig?: WorkflowConfigResolvers<ContextType>;
  WorkflowConfigState?: WorkflowConfigStateResolvers<ContextType>;
  WorkflowConfigStateRulePayout?: WorkflowConfigStateRulePayoutResolvers<ContextType>;
  WorkflowConfigStateRulePayoutTypeBetween?: WorkflowConfigStateRulePayoutTypeBetweenResolvers<ContextType>;
  WorkflowConfigStateRulePayoutTypeChecker?: WorkflowConfigStateRulePayoutTypeCheckerResolvers<ContextType>;
  WorkflowConfigStateRulePayoutTypeMergeStates?: WorkflowConfigStateRulePayoutTypeMergeStatesResolvers<ContextType>;
  WorkflowConfigStateTransition?: WorkflowConfigStateTransitionResolvers<ContextType>;
  WorkflowConfigTemplate?: WorkflowConfigTemplateResolvers<ContextType>;
  WorkflowCreator?: WorkflowCreatorResolvers<ContextType>;
  WorkflowState?: WorkflowStateResolvers<ContextType>;
  WorkflowStateAction?: WorkflowStateActionResolvers<ContextType>;
  WorkflowStateActionActor?: WorkflowStateActionActorResolvers<ContextType>;
  WorkflowStateDates?: WorkflowStateDatesResolvers<ContextType>;
  merchantConfigurationUpdateResponse?: MerchantConfigurationUpdateResponseResolvers<ContextType>;
  userOtpResponse?: UserOtpResponseResolvers<ContextType>;
};
