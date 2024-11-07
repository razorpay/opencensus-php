export enum CUSTOMER_RISK_CATEGORY {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  ALL = 'all',
}

export enum PREPAID_PAYMENY_AMOUNT_ITEM_TYPE {
  PERCENTAGE = 'percentage',
  FLAT = 'flat',
}

export enum PARTIAL_COD_TYPE {
  BASIC = 'basic',
  ADVANCED = 'advanced',
}

export type CustomerRiskCategory =
  | CUSTOMER_RISK_CATEGORY.HIGH
  | CUSTOMER_RISK_CATEGORY.MEDIUM
  | CUSTOMER_RISK_CATEGORY.LOW;

export type State = {
  isLoading: boolean;
  error: string | null;
  partialCODConfigs: PartialCODConfigs | {};
  isPartialCODEnabled: boolean;
};

export type PrepaidPaymentAmountItemType =
  | PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
  | PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE;

export type PartialCODConfigsType = PARTIAL_COD_TYPE.ADVANCED | PARTIAL_COD_TYPE.BASIC;

export type PrepaidPaymentAmountItem = {
  type: PrepaidPaymentAmountItemType;
  value: number;
  rules: {
    min_order_amount: number;
    max_order_amount?: number;
    customer_risk_category: CustomerRiskCategory[];
  };
};

export type PartialCODConfigs = {
  type: PartialCODConfigsType;
  prepaid_payment_amount: PrepaidPaymentAmountItem[] | [];
};

export type ApiCallbackType =
  | {
      onSuccess?: (data?: PartialCODConfigs) => void;
      onError?: () => void;
      onEnd?: () => void;
    }
  | undefined;

export type PartialCODContextProps = {
  configsLocal: PartialCODConfigs;
  handleAddSlab: (data: PrepaidPaymentAmountItem, callbacks?: ApiCallbackType) => void;
  handleRemoveSlab: (index: number, callbacks?: ApiCallbackType) => void;
  handleRiskCategory: (
    riskType: Exclude<CustomerRiskCategory, CUSTOMER_RISK_CATEGORY.ALL>,
    index: number,
  ) => void;
  handleUpdateSlabType: (targetValue: PartialCODConfigsType, callbacks?: ApiCallbackType) => void;
  handleUpdateSlab: (
    index: number,
    data: PrepaidPaymentAmountItem,
    callbacks?: ApiCallbackType,
  ) => void;
  handleUpdatePartialCODStatus: (status: boolean, callbacks?: ApiCallbackType) => void;
  isPartialCODEnabledLocal: boolean;
  saveConfigs: () => void;
  setConfigsLocal: (configs: PartialCODConfigs) => void;
  setIsPartialCODEnabledLocal: (isPartialCODEnabledLocal: boolean) => void;
  configsToShow: PrepaidPaymentAmountItem[] | [];
  showNotification: (obj: any) => void;
};

export type AdvancedSlabModalProps = {
  configData?: PrepaidPaymentAmountItem;
  onConfirm: (slabData: PrepaidPaymentAmountItem, callbacks: ApiCallbackType) => void;
  isOpen: boolean;
  onClose: () => void;
};

export type AdvancedSlabModalDataType = {
  type: PrepaidPaymentAmountItemType;
  value: string;
  rules: {
    min_order_amount: string;
    max_order_amount?: string;
    customer_risk_category: CustomerRiskCategory[];
  };
};

export type BasicSlabChipsProps = {
  activeBasicSlab: PrepaidPaymentAmountItem;
  setActiveBasicSlab: React.Dispatch<React.SetStateAction<PrepaidPaymentAmountItem>>;
};

export type BasicSlabModalProps = {
  onSave: (value: number, type: string) => void;
  isOpen: boolean;
  onClose: () => void;
};

export type RiskCategoryDropdownProps = {
  value: CustomerRiskCategory[] | [] | undefined;
  onChange: (value: CustomerRiskCategory[] | []) => void;
  label?: string;
  labelPosition?: 'left' | 'top' | 'inside-input';
  validationState?: 'none' | 'error' | 'success' | undefined;
};
