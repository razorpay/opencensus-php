import IUser from 'merchant/models/User';
import { BannerType } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner/config';
import { AnyObject } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/typings';

export type Device = {
  isMobile: boolean;
};

export type InitialState = Record<string, never>;

export type Session = {
  user: IUser;
  profile: Record<string, any>;
  org: Record<string, any>;
};

export type ModalActions = {
  openModal: (payload: ModalPayload) => void;
  closeModal: () => void;
};

export type NotificationAction = {
  showNotification: (payload: { type: string; message: string }) => void;
};

export enum FIELD_TYPE {
  CHIP = 'chip',
}

export enum FILE_CHANGE_ACTION {
  ADD = 'ADD',
  REMOVE = 'REMOVE',
}

export enum FLOW_TYPE {
  UPDATE = 'update',
  SWITCH = 'switch',
}

export enum BANK_ACCOUNT_UPDATE_STEPS {
  BANK_ACCOUNT_FORM = 'BANK_ACCOUNT_FORM',
  LOADING_VIEW = 'LOADING_VIEW',
  PENNY_TESTING_RETRY = 'PENNY_TESTING_RETRY',
  UPLOAD_PROOF = 'UPLOAD_PROOF',
  NEEDS_CLARIFICATION = 'NEEDS_CLARIFICATION',
  PENNY_TESTING_INPUT_ERROR = 'PENNY_TESTING_INPUT_ERROR',
  PENNY_TESTING_SUCCESS = 'PENNY_TESTING_SUCCESS',
}

export enum LOADING_STATE {
  UPLOAD_VERIFICATION_LETTER_DETAIL = 'UPLOAD_VERIFICATION_LETTER_DETAIL',
  UPLOAD_CANCELLED_CHEQUE_DETAIL = 'UPLOAD_CANCELLED_CHEQUE_DETAIL',
  UPLOAD_NC_BANK_DETAIL = 'UPLOAD_NC_BANK_DETAIL',
  PENNY_TESTING_INPROGRESS = 'PENNY_TESTING_INPROGRESS',
  PENNY_TESTING_SUCCESS = 'PENNY_TESTING_SUCCESS',
  UPLOAD_NC_IE_DETAILS = 'UPLOAD_NC_IE_DETAILS',
}

export interface LoadingStepData {
  title: string;
  subTitle: string;
  animationData: unknown;
  description?: string;
  closeCTALabel?: string;
  mobileSubTitle?: string;
}

export interface BankDataInterface {
  id: string;
  name: string;
  value: string;
  type?: string;
}

interface Action {
  title: string;
  isDisable: boolean;
}
export interface ActionPayload {
  bankDetails?: BankDataInterface[];
  flowType?: FLOW_TYPE;
}

export interface BankDetailsPropsInterface {
  switchAction?: Action;
  bankData: BankDataInterface[];
  handleAction: (payload: ActionPayload) => void;
}

export interface AccountData {
  title: string;
  description: string;
  banks: BankDataInterface[][];
}

interface ModalPayload {
  size: string;
  component: JSX.Element;
  queryParams?: Record<string, string>;
}

export interface AccountSectionPropsInterface extends Device {
  id: string;
  switchAction?: Action;
  isCtaAction?: boolean;
  isCollapsible?: boolean;
  accountData: AccountData;
  user: IUser;
  handleAction: (payload: ActionPayload) => void;
}

export interface BankData {
  id: string;
  entity: string;
  ifsc: string;
  bank_name: string;
  name: string;
  account_number: string;
  updated_at: number;
}

export interface SettlementInfoType {
  isHold: boolean;
  hold_type: BannerType.COMPLETE_KYC | BannerType.RISK_FOH | BannerType.SOH | null;
  message?: string;
}

export interface useAccountHookInterface {
  previousBanks: AccountData | InitialState;
  activeBank: AccountData | InitialState;
  settlementStatus: SettlementInfoType | InitialState;
  isUpdateEnable: boolean;
}

export interface BankAccountDetailsPropsInterface
  extends Session,
    ModalActions,
    NotificationAction,
    Device {
  settlementConfig: Record<string, any>;
  previous_banks: { data: BankData };
  fetchBankAccount: () => Promise<void>;
  fetchPreviousBankDetails: () => Promise<void>;
  fetchSettlementConfig: () => Promise<void>;
  fetchBankAccountChangeStatus: (id: string) => Promise<Record<string, any>>;
  fetchWorkflowStatus: (workflow: string) => Promise<void>;
}

export interface AccountInfoPayloadInterface {
  type: string;
  data: BankData[];
  isSettlementOnhold?: boolean;
  details: BankData;
}

interface Config {
  status: boolean;
  reason: string;
}

export interface SettlementInfoPayload {
  global_hold_config: Config;
  hold: Config;
  bankAccount: Record<string, string>;
  user: IUser;
}

export interface HeaderConfig {
  title?: string;
  close?: boolean;
}

export interface ConfigInterface {
  component: React.ReactNode;
  header?: HeaderConfig;
  isCentered?: boolean;
  minHeight?: number;
}

export type StepsConfigInterface = {
  [key in BANK_ACCOUNT_UPDATE_STEPS]: ConfigInterface;
};

export interface BankAccountUpdateFlowPropInterface extends Device, ModalActions {
  defaultView: string;
  user: IUser;
  showNotification: (payload: { type: string; message: string }) => void;
}

export interface LoadingStepInterface extends ModalActions, Device {
  type: LOADING_STATE;
  lottieClass?: string[];
  lottieDivWidth?: number;
}

export interface StepsInfoPropsInterface extends ModalActions, Device {
  view: string;
  setView: (view: BANK_ACCOUNT_UPDATE_STEPS) => void;
  state: IformState;
  setState: React.Dispatch<React.SetStateAction<IformState>>;
  setLayoutInfo: React.Dispatch<React.SetStateAction<BANK_ACCOUNT_UPDATE_STEPS | ''>>;
}

export interface FileChangeArgs {
  id: LOADING_STATE;
  type: FILE_CHANGE_ACTION;
  file?: any;
}

export interface FileUploadSectionInterface {
  activeTab: LOADING_STATE;
  extras: {
    acceptFiles: string[];
  };
  onFileLimitFailure: () => void;
  handleFileChange: (payload: FileChangeArgs) => void;
  files: any[];
}

export interface InputFormPropsInterface
  extends ModalActions,
    Session,
    StepsInfoPropsInterface,
    NotificationAction {
  saveBankAccountChangesAutomate: (userId: string, formData: FormData) => Promise<AnyObject>;
  fetchBankAccount: () => Promise<void>;
  fetchWorkflowStatus: (workflow: string) => Promise<void>;
}

export interface InputErrorPropsInterface extends ModalActions, Device {
  setView: (BANK_ACCOUNT_UPDATE_STEPS) => void;
  state: IformState;
  setState: React.Dispatch<React.SetStateAction<IformState>>;
}

export interface FormDataInterface {
  description: {
    title: string;
    details: {
      title: string;
      items: string[];
    };
    video?: {
      link: string;
    };
  };
  document: {
    subtitle: string;
  };
}

export type FROM_DATA_TYPES = {
  [key in LOADING_STATE]: FormDataInterface;
};

export type BankAccountUpdateErrorData = {
  title: string;
  subTitle: string;
  icon: BVS_INPUT_ERROR_ICON;
};

export enum BankAccountUpdateErrorCodeEnum {
  'INVALID_BENEFICIARY_ACCOUNT_NUMBER' = 'KC03',
  'INVALID_ACCOUNT' = 'KC27',
  'INVALID_IFSC_OR_NBIN' = 'KC40',
  'ACCOUNT_BLOCKED' = 'KC05',
  'NRE_ACCOUNT' = 'KC06',
  'ACCOUNT_CLOSED' = 'KC07',
}

export enum BVS_INPUT_ERROR_ICON {
  USER_ERROR = 'USER_ERROR',
  BANK_ERROR = 'BANK_ERROR',
}

export enum INPUT_VALIDATION_STATES {
  ERROR = 'error',
  NONE = 'none',
}

export interface ModalLayoutInterface extends Omit<ConfigInterface, 'component'>, ModalActions {
  children: JSX.Element;
}

export interface IinputData {
  account_number: string;
  account_number_confirmation: string;
  ifsc_code: string;
  beneficiary_name: string;
}

export interface IinputValidation {
  account_number: INPUT_VALIDATION_STATES;
  account_number_confirmation: INPUT_VALIDATION_STATES;
  ifsc_code: INPUT_VALIDATION_STATES;
  beneficiary_name: INPUT_VALIDATION_STATES;
}
export interface IformState {
  inputData: IinputData;
  inputValidation: IinputValidation;
  bvs_error_code: string;
  retries: number;
}

export type Bank = {
  BANK: string;
  BRANCH: string;
};

export interface WorkflowConfigInterface {
  workflows: AnyObject;
  workflowName: string;
  workflowType: string;
  fetchWorkflowStatus: (payload: string) => Promise<AnyObject>;
}

export interface UseBankAccountDetailsInterface
  extends Pick<BankAccountDetailsPropsInterface, 'org' | 'user' | 'settlementConfig'> {
  isBankUpdateEnabled: boolean | null;
  isDataSettled: boolean;
  bankAccount: Record<string, string>;
}

export interface UploadProofsPropsInterface
  extends StepsInfoPropsInterface,
    Session,
    NotificationAction {
  fetchWorkflowStatus: (workflow: string) => Promise<void>;
}
