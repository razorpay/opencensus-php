import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
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
  InternationalSettingsFields,
} from './section';
import { Store } from 'common/typings';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';

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
  NAME = 'name',
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
  profile?: any;
  isRevampedInfo?: boolean;
  dataConfig: StoredInfoDataInterface[];
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
    | PricingFields
    | InternationalSettingsFields;
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
  editTooltip?: TooltipInterface;
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

export interface UserInfoPropsI {
  isMobile?: isMobile;
  infoData: InfoDataInterface[];
  onClick: (arg0: InfoDataInterface) => void;
}

export interface UserInfoPropsInterface extends UserInfoPropsI {
  updateMerchantConfig: () => Promise<UpdateMerchantConfigI>;
  updateSession: () => void;
  showNotification: () => void;
  user: Store['session']['user'];
}

export interface AdditionalContextInterface extends FeatureInterface {
  user: User;
  instruments: Instruments[];
  shouldShowApplications: boolean;
  mode: string;
  websiteSectionDetailsData: WebsiteSectionDetailsInterface;
  extraConfig: ExtraConfig;
  profile: any;
  [key: string]: unknown;
}

interface ModalPayloadInterface {
  size: string;
  component: JSX.Element;
  queryParams?: Record<string, string>;
}

interface UpdateMerchantConfigI {
  id: string;
  name: string;
  fee_bearer: string;
  transaction_report_email: Array<string>;
  invoice_label_field: string;
  auto_capture_late_auth: false;
  brand_color: string;
  handle: string | null;
  logo_url: string | null;
  fee_credits_threshold: number;
  amount_credits_threshold: number;
  refund_credits_threshold: number;
  balance_threshold: number;
  display_name: string;
  default_refund_speed: string;
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
  updateMerchantConfig: () => Promise<UpdateMerchantConfigI>;
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

export interface ProfileViewpropsInterface {
  isMobile: isMobile;
  user: User;
  userRole: string;
  handleEditClick: () => void;
  profile: Record<string, unknown>;
}

export interface ActiveModalI {
  id: PersonalProfileFields;
  displayName: string;
  updateMerchantConfig: (
    args: { display_name?: string; name?: string },
    callback: () => void,
  ) => void;
}
