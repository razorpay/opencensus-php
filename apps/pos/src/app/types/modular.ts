import { MODULAR_DEVICE_FIELDS } from './DeviceSelection';

// JSON type for meta and other JSONObject fields
type JSONObject = Record<string, string>;

export type MerchantModularOnboardingDetailsResponse =
  | MerchantModularOnboardingDetailsSuccessResponse
  | MerchantModularOnboardingDetailsFailureResponse;

export type MerchantModularOnboardingDetailsUpdateResponse =
  | MerchantModularOnboardingDetailsSuccessResponse
  | MerchantModularOnboardingDetailsFailureResponse;

export type ModularOnboardingField =
  | ModularOnboardingFieldWithStringValue
  | ModularOnboardingFieldWithStringArrayValue
  | ModularOnboardingFieldWithBooleanValue
  | ModularOnboardingFieldForDocumentUpload
  | ModularOnboardingFieldForDeviceCharges
  | ModularOnboardingFieldForOrderSummaryItem
  | ModularOnboardingFieldForBrands
  | ModularOnboardingFieldForArrayOfDocumentsUpload
  | ModularOnboardingFieldForJsonValues;

// Failure Response
export interface MerchantModularOnboardingDetailsFailureResponse {
  code: number;
  success: boolean;
  message: string;
  meta?: JSONObject;
  __typename: string;
}

// Success Response
export interface MerchantModularOnboardingDetailsSuccessResponse {
  success: boolean;
  workflowData: WorkflowData;
  onboardingState: OnboardingState;
  countryCode: string;
  onboardingType: string;
  merchantType: string;
  __typename: string;
}

// Onboarding State
export interface OnboardingState {
  milestones: string[];
  steps: string[];
  modularComponents: string[];
}

// Workflow Data
export interface WorkflowData {
  id: string;
  milestones: ModularOnboardingMilestone[];
  progress: number;
  status: string;
}

// Modular Onboarding Milestone
export interface ModularOnboardingMilestone {
  name: string;
  status: string;
  canSubmit?: boolean;
  steps: ModularOnboardingStep[];
  progress: number;
  meta: MilestoneMeta;
}

// Union type for Modular Onboarding Step
export type ModularOnboardingStep =
  | ModularOnboardingStepWithModularComponents
  | ModularOnboardingStepWithSteps;

// export interface  for Modular Onboarding Step
export interface ModularOnboardingStepParent {
  name: string;
  status: string;
  progress: number;
  meta: StepMeta;
}

// Modular Onboarding Step with Modular Components
export interface ModularOnboardingStepWithModularComponents extends ModularOnboardingStepParent {
  modularComponents: ModularComponent[];
}

// Modular Onboarding Step with Steps
export interface ModularOnboardingStepWithSteps extends ModularOnboardingStepParent {
  steps: ModularOnboardingStepParent[];
}

// Modular Component
export interface ModularComponent {
  name: string;
  status: string;
  fields: ModularOnboardingField[];
  progress: number;
  meta: ModularComponentMeta;
  isRequired: boolean;
}

// CTA Types
export interface CtaTypes {
  primary?: string;
  secondary?: string;
  tertiary?: string;
  linkButton?: string;
}

// API Action
export interface APIAction {
  onLoad: string;
}

// Modular Component Meta
export interface ModularComponentMeta {
  template?: string;
  title?: string;
  description?: string;
  errorCode?: string;
  tooltipBadge?: TooltipBadge;
  validations?: JSONObject;
  metaUi?: ModularComponentMetaUi;
  isHidden?: boolean;
  deviceConfig?: DeviceConfig[];
  brandDataFields?: string[];
  optionalBrandFields?: string[];
  merchantGstField?: string;
  isLast?: boolean;
  type?: string;
  apiActions?: APIAction;
  hideCtas?: boolean;
  contents?: ContentMeta[];
  ctas?: CtaTypes;
  ctaMeta?: JSONObject;
  modals?: JSONObject;
  isConfirmationComponent?: boolean;
  ruleEngine?: JSONObject;
  defaultValues?: Record<string, number>;
  acquirerPreferenceOptions?: ModularOnboardingOption[];
}

// Content Meta
export interface ContentMeta {
  label?: string;
  iconName?: string;
}

// Tooltip Badge
export interface TooltipBadge {
  tooltipContent: string;
  tooltipPlacement?: string;
  tooltipTitle?: string;
  badgeSize?: string;
  badgeColor?: string;
  badgeContent: string;
}

// Modular Component Meta UI
export interface ModularComponentMetaUi {
  fields: ModularComponentMetaUiField[];
}

// export interface  for Modular Onboarding Field
export interface ModularOnboardingFieldParent {
  name: string;
  isDisabled: boolean;
  isRequired: boolean;
  isHidden?: boolean;
  isInternal?: boolean;
  meta?: ModularOnboardingFieldMeta;
  failureReason?: string;
  failureReasonType?: string;
}

// Modular Onboarding Field with String Value
export interface ModularOnboardingFieldWithStringValue extends ModularOnboardingFieldParent {
  stringValue: string;
}

// Modular Onboarding Field with String Array Value
export interface ModularOnboardingFieldWithStringArrayValue extends ModularOnboardingFieldParent {
  stringArrayValue: string[];
}

// Modular Onboarding Field with Boolean Value
export interface ModularOnboardingFieldWithBooleanValue extends ModularOnboardingFieldParent {
  booleanValue: boolean;
}

// Modular Onboarding Field for Document Upload
export interface ModularOnboardingFieldForDocumentUpload extends ModularOnboardingFieldParent {
  documentUploadValue: DocumentUploadFieldValue;
}

// Modular Onboarding Field for Array of Documents Upload
export interface ModularOnboardingFieldForArrayOfDocumentsUpload
  extends ModularOnboardingFieldParent {
  arrayOfDocumentsUploadValue: ArrayOfDocumentFieldsUpload[];
}

// Modular Onboarding Field for Device Charges
export interface ModularOnboardingFieldForDeviceCharges extends ModularOnboardingFieldParent {
  orderSummary: DeviceCharges;
}

// Modular Onboarding Field for JSON Values
export interface ModularOnboardingFieldForJsonValues extends ModularOnboardingFieldParent {
  value: JSONObject;
}

// Modular Component Meta UI Field
export interface ModularComponentMetaUiField {
  name: string;
  value: string;
  meta: ModularComponentMetaUiFieldMeta;
}

// Document Upload Field Value
export interface DocumentUploadFieldValue {
  fileId?: string;
  name?: string;
  size?: number;
  fileStoreId?: string;
}

// Modular Onboarding Field Meta
export interface ModularOnboardingFieldMeta {
  template?: string;
  title?: string;
  description?: string;
  defaultValue?: string;
  dataType: string;
  options?: ModularOnboardingOption[];
  validations?: JSONObject[];
  size?: string;
  selectionType?: string;
  accessibilityLabel?: string;
  accept?: string;
  helpText?: string;
  maxCount?: number;
  maxSize?: number;
  uploadType?: string;
  documentType?: string;
  hideOnReviewScreen?: boolean;
  reviewScreenLabel?: string;
  placeholder?: string;
  disableOnSelected?: string;
  canSubmitOnChange?: boolean;
  jsonValue?: ModularOnboardingFieldJsonValue;
}

export interface CurrentDeviceDetails {
  details_page_name: string;
  device_model: string;
  device_order_item_id: string;
  device_serial: string;
  display_label: string;
  display_name: string;
  icon: string;
  id: string;
  mapped_vpa: string;
  mapping_status: string;
  plan_name: string;
  setup_charge: number;
}

interface ModularOnboardingFieldJsonValue {
  data_type: string;
  device_deployment_details?: CurrentDeviceDetails;
  device_deployment_details_list?: JSONObject[];
  is_hidden: boolean;
  merchant_id: boolean;
}

// Modular Component Meta UI Field Meta
export interface ModularComponentMetaUiFieldMeta {
  title?: string;
  description?: string;
  defaultValue?: string;
  dataType: string;
  options?: ModularOnboardingOption[];
  size?: string;
  selectionType?: string;
  accessibilityLabel?: string;
  redirectUrl?: ModularComponentMetaUiFieldMetaRedirectUrl;
}

// Modular Component Meta UI Field Meta Redirect URL
export interface ModularComponentMetaUiFieldMetaRedirectUrl {
  milestone?: string;
  step?: string;
  component?: string;
}

// Modular Onboarding Option
export interface ModularOnboardingOption {
  label: string;
  value: string;
  helpText?: string;
}

// Step Meta
export interface StepMeta {
  title?: string;
  description?: string;
  template?: string;
  isLast?: boolean;
  type?: string;
  linkedSteps?: string[];
  apiActions?: APIAction;
  ctas?: CtaTypes;
  ctaMeta?: JSONObject;
}

// Milestone Meta
export interface MilestoneMeta {
  template?: string;
  isLast?: boolean;
}

// Modular Onboarding Config
export interface ModularOnboardingConfig {
  milestones: ModularOnboardingMilestone[];
}

// Modular Onboarding Data
export interface ModularOnboardingData {
  config: ModularOnboardingConfig;
  state: OnboardingState;
}

// Merchant Modular Onboarding Component Input
export interface MerchantModularOnboardingComponentInput {
  key: string;
  properties: MerchantModularOnboardingComponentPropertiesInput;
}

// Merchant Modular Onboarding Component Properties Input
export interface MerchantModularOnboardingComponentPropertiesInput {
  isSkipped: boolean;
}

// Modular Onboarding Field for Order Summary Item
export interface ModularOnboardingFieldForOrderSummaryItem extends ModularOnboardingFieldParent {
  addedDevices: DeviceOrderSummaryItem[];
}

// Device Order Summary Item
export interface DeviceOrderSummaryItem {
  deviceName: string;
  itemId: string;
  paperRollCharge?: number;
  paperRollQuantity?: number;
  quantity?: number;
  renewal?: string;
  rentalCharge?: number;
  setupCharge?: number;
  totalAdvanceRentalCharge?: number;
  totalPaperRollCharge?: number;
  totalRentalCharge?: number;
  totalSetupCharge?: number;
  rentalChargeType?: string;
  setupChargeType?: string;
}

// Rental Charge
export interface RentalCharge {
  deviceName: string;
  fee: number;
  gst?: number;
  renewal?: string;
}

// Device Charges
export interface DeviceCharges {
  advanceRentalCharge?: number;
  deviceCharge?: number;
  gst?: number;
  orderId?: string;
  paperRollCharge?: number;
  rentalCharge?: RentalCharge[];
  shippingCharge?: number;
  totalOrderCharge?: number;
  totalRentalCharge?: number;
}

// Plan Config
export interface PlanConfig {
  planName: string;
  setupFee?: number;
  rentalCharge?: number;
  oneTimeCharge?: number;
  planDisplayName: string;
}

// Rate Config
export interface RateConfig {
  active?: boolean;
  name?: string;
  paperRollCharges?: number;
  rentalDiscountMonths?: number;
  advancedRentalMonths?: number;
  plans?: PlanConfig[];
}

// Device Config
export interface DeviceConfig {
  defaultValues?: JSONObject;
  title?: string;
  icon?: string;
  rateConfig?: RateConfig[];
}

// Array of Document Fields Upload
export interface ArrayOfDocumentFieldsUpload {
  name?: string;
  size?: number;
  fileStoreId?: string;
}

export type ModularPayload = Partial<Record<MODULAR_DEVICE_FIELDS, unknown>>;

// Modular Onboarding Field for Order Summary Item

export enum BRAND_EMI_VERIFICATION_STATUS_ENUM {
  VERIFIED = 'verified',
  FAILED = 'failed',
  PENDING = 'pending',
  REJECTED = 'rejected',
}
// Brand emi summary item
export interface BrandItem {
  name: string;
  dealerCode?: string;
  distributorCode?: string;
  stateCode?: string;
  merchantGst?: string;
  verificationDetailsId: string;
  verificationStatus: BRAND_EMI_VERIFICATION_STATUS_ENUM;
}
export interface ModularOnboardingFieldForBrands extends ModularOnboardingFieldParent {
  addedBrands: BrandItem[];
}
