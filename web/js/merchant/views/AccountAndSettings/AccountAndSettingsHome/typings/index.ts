import { RouteComponentProps } from 'react-router-dom';
import {
  BankAccountSettlementFields,
  BusinessSettingsFields,
  CheckoutSettingsFields,
  NotificationSettingsFields,
  PaymentMethodsFields,
  PaymentRefundsFields,
  PricingFields,
  SectionCardDataFields,
  WebsiteAppSettingsFields,
} from './section';

export type isMobile = boolean | undefined;
export type User = Record<string, any>;

export enum HANDLERS {
  UPDATE = 'UPDATE',
  ADD = 'ADD',
}
enum QUERY_PARAM {
  CHANGE_PASSWORD = 'change-password',
  UPDATE_DISPLAY_NAME = 'update-display-name',
  UPDATE_LOGIN_EMAIL = 'update-login-email',
  UPDATE_CONTACT_NUMBER = 'update-contact-number',
}
export enum PersonalProfileFields {
  DISPLAY_NAME = 'display_name',
  CONTACT_MOBILE = 'contact_mobile',
  EMAIL = 'email',
  PASSWORD = 'password',
}

interface WebsiteSectionData {
  isGracePeriodApplicable: boolean;
  isWebsiteSectionsApplicable: boolean;
}
export interface WebsiteSectionDetailsInterface {
  data: WebsiteSectionData;
  loading: boolean;
  error: boolean | any;
}

export type AdditionalConditionType = (
  payload: AdditionalContextInterface,
) => (payload: any) => void;

export interface Instruments {
  name: string;
  description: string;
  slug: string;
  icon: string;
  merchant_instrument_request_id: string;
  path: string;
  status: string;
  created_at: number;
  fade_comment: string;
}

interface NotificationPayload {
  type: string;
  message: string;
}

interface FeaturePayloadInterface {
  userId: string;
  feature: string;
}

interface FeatureResponse {
  data: Record<string, boolean>;
  loading: boolean;
}
export interface AccountAndSettingsHomePropInterface {
  isMobile?: isMobile;
  user: User;
  profile: any;
  mode: string;
  instruments: Instruments[];
  shouldShowApplications: boolean;
  loading: boolean;
  websiteSectionDetailsData: WebsiteSectionDetailsInterface;
  fetchMerchantWebsiteDetailsFn: () => Promise<void>;
  fetchFeatureByNameFn: (payload: FeaturePayloadInterface) => Promise<void>;
  fetchMerchantInstrumentsFn: () => Promise<void>;
  fetchRequestedInstrumentsFn: () => Promise<void>;
  fetchEnrollmentStatus: () => Promise<void>;
  fetchConnectedApplicationsFn: () => Promise<void>;
  fetchOauthConnectedApplications: () => Promise<void>;
  showNotificationFn: (payload: NotificationPayload) => Promise<void>;
  setLoadingFn: () => Promise<void>;
  featureStatusConfig: FeatureResponse;
  enrollmentStatus: {
    loading: boolean;
    hasEnrolled: boolean | null;
    message: string | null;
    error: unknown;
  };
}

export interface InfoDataPayload {
  user: User;
  profile: any;
}

export interface FeatureInterface {
  loading?: boolean;
  allowCFBInternational: boolean;
}

export interface VerificationPropsInterface {
  isMobile?: isMobile;
  user: User;
}
export interface SubSection {
  id:
    | PaymentMethodsFields
    | WebsiteAppSettingsFields
    | BusinessSettingsFields
    | PaymentRefundsFields
    | NotificationSettingsFields
    | CheckoutSettingsFields
    | BankAccountSettlementFields
    | PricingFields;
  href: string;
  title: string;
  additionalCondition?: AdditionalConditionType;
  isNew?: boolean;
  onLinkClick?: () => void;
}

export interface SectionCardInterface {
  id: SectionCardDataFields;
  title: string;
  icon: string;
  iconBackground: string;
  additionalCondition?: AdditionalConditionType;
  subSections: SubSection[];
}

export interface SectionCardPropsInterface extends SectionCardInterface, RouteComponentProps {
  isMobile: isMobile;
}

interface TooltipInterface {
  description: string;
}
export interface InfoDataInterface {
  id: PersonalProfileFields;
  displayName: string;
  tooltip?: TooltipInterface;
  value: string;
  isEditEnable: boolean;
  handlerType?: HANDLERS;
  queryParam?: QUERY_PARAM;
  isCriticalFlowEnabled?: boolean;
  selfServeActionName: string;
  analyticsEventInfo: {
    objectName: string;
    actionName: string;
    properties?: Record<string, string>;
  };
}

export interface StoredInfoDataInterface extends InfoDataInterface {
  getValue: (arg0: { user: User }) => string;
  shouldEdit: (arg0: { user: User }) => boolean;
  isVisible: (arg0: any) => boolean;
}

export type ActualInfoDataInterface = Omit<
  InfoDataInterface,
  'getValue' | 'shouldEdit' | 'isVisible'
>;

export interface UserInfoPropsInterface {
  isMobile?: isMobile;
  infoData: InfoDataInterface[];
  onClick: (arg0: InfoDataInterface) => void;
}

export interface AdditionalContextInterface extends FeatureInterface {
  user: User;
  instruments: Instruments[];
  shouldShowApplications: boolean;
  mode: string;
  websiteSectionDetailsData: WebsiteSectionDetailsInterface;
  profile: any;
  [key: string]: unknown;
}

interface ModalPayloadInterface {
  size: string;
  component: JSX.Element;
  queryParams?: Record<string, string>;
}
export interface ProfilePropsInterface {
  user: User;
  isMobile?: isMobile;
  profile: any;
  openModal: (payload: ModalPayloadInterface) => void;
  updateSession: (payload: { user: User }) => Promise<void>;
  showNotification: (payload: NotificationPayload) => void;
  updateUser: () => Promise<void>;
  closeModal: () => void;
  updateMerchantConfig: () => Promise<void>;
}

export interface FormConfigInterface {
  attributes?: Record<string, unknown>;
  Component: (props: FormConfigInterface['attributes']) => JSX.Element;
}

export interface FormPayloadConfigInterface {
  props: ProfilePropsInterface;
  id: PersonalProfileFields;
  handlerType?: HANDLERS;
}

export interface AccountAndProductSectionPropsInterface {
  isMobile?: isMobile;
  sections: SectionCardInterface[];
}
