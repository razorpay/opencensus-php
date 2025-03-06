export const CampaignTypeEnum = {
  TRIGGER: 'trigger',
  ONE_TIME: 'one-time',
};

export const CreditTypeEnum = {
  FLAT: 'flat',
  PERCENTAGE: 'percentage',
};

export type CampaignType = (typeof CampaignTypeEnum)[keyof typeof CampaignTypeEnum];

export type AttributeTypes =
  | 'string'
  | 'int'
  | 'int64'
  | 'uint'
  | 'uint64'
  | 'float'
  | 'boolean'
  | 'map';

export type PrimitiveAttributeTypes = Exclude<AttributeTypes, 'map'>;

export type AttributeOperatorOption = {
  value: string;
  label: string;
  rule: string;
};

export interface AttributeFormField {
  id: string;
  field: string;
  operator: string;
  value?: string;
  minValue?: string;
  maxValue?: string;
  type?: AttributeTypes;
}

export interface FormData {
  triggerEvent: string;
  triggerAttributes: AttributeFormField[];
  triggerAction: string;
  selectedWallet: string;
  creditType: (typeof CreditTypeEnum)[keyof typeof CreditTypeEnum];
  creditAmount: string;
  triggerActionAttribute: string;
  noMaxLimit: boolean;
  maxCredit: string;
  expiryDuration: string;
  expiryDurationPreset: string;
  minOrderValue: string;
  startDate: Date | null;
  startTime: string;
  startImmediately: boolean;
  endDate: Date | null;
  endTime: string;
  noEndDate: boolean;
  campaignLimitAmountEnabled: boolean;
  campaignLimitAmount: string;
  campaignLimitAmountPeriod: string;
  campaignLimitActionsEnabled: boolean;
  campaignLimitActions: string;
  campaignLimitActionsPeriod: string;
  userLimitAmountEnabled: boolean;
  userLimitAmount: string;
  userLimitAmountPeriod: string;
  userLimitActionsEnabled: boolean;
  userLimitActions: string;
  userLimitActionsPeriod: string;
}

export interface Wallet {
  id: string;
  name: string;
}

export interface Attribute {
  type: AttributeTypes;
  required: boolean;
}

export type ValueOf<T> = T[keyof T];
