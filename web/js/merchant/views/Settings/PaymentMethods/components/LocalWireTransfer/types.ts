export type ActivateAccountResponseType = {
  status: number;
  success?: boolean;
  data?: Array<AccountType>;
};

export type ActivateAccountBodyType = {
  va_currency: string;
  accept_b2b_tnc: boolean;
  type: string;
};

export type AccountType = {
  va_currency: string;
  routing_code: string;
  routing_type: string;
  account_number: string;
  beneficiary_name: string;
  bank_name: string;
  bank_address: string;
  status: string;
};

export type ListItemType = {
  icon?: string;
  description?: string;
  message?: string;
  name: string;
  status: string;
  slug: string;
  vaCurrency: string;
};

export type LeafListType = {
  listHeader?: string;
  listDescription?: string;
  list: Array<ListItemType>;
};

export type ContainerErrorType = {
  message: string;
  action: JSX.Element;
};

export type DetailFieldType = {
  label: string;
  key: string;
};

export type BankTransferConfigType = {
  accounts: Array<AccountType>;
  isFetching: boolean;
  shouldShowAction: boolean;
  shouldShowListAction: boolean;
  containerStatus: string;
  containerError: ContainerErrorType | boolean;
  isRequestButtonDisabled: boolean;
  publicPaymentLink: string | undefined;
};

export type FetchB2BResponse = {
  data: {
    accounts?: Array<AccountType>;
  };
};

export interface LocalWireTransferPropsInterface {
  leafList: LeafListType;
  config: BankTransferConfigType;
  apiError: { errors?: Array<string> };
  purposeCode?: string;
  isIneligiblePurposeCodeModalOpen: boolean;
  setPublicPaymentLink: (link: string) => void;
  fetchPurposeCode: () => void;
  fetchB2bAccounts: () => Promise<FetchB2BResponse>;
  showNotification: (payload: { type: string; message: unknown }) => void;
  openModal: (payload: { size: string; component: JSX.Element }) => void;
  onMoneySaverAccountsActivated: (status: boolean) => void;
  data: unknown;
}

enum Account {
  USD = 'USD',
  SWIFT = 'SWIFT',
}

export type AcknowledgementPopupProps<ReduxProps> = {
  account?: Account;
  showTnC?: boolean;
  purposeCode?: string;
} & ReduxProps;

export interface TogglePropsInterface {
  isOpen: boolean | string;
  currency: string;
  onToggleClick: () => void;
}

export interface InstrumentRowPropsInterface {
  accounts: Array<AccountType>;
  data: ListItemType;
  isOpen: boolean | string;
  isLoading: boolean;
  setIsOpen: (currency: boolean | string) => void;
  props: unknown;
}

export interface BankTransferConfigInterface {
  accounts: Array<AccountType>;
  isFetching: boolean;
  user: { promoter_pan_name: string | null };
  fircData: { data: { purpose_code: string | null } };
  openModal: (payload: { size: string; component: JSX.Element }) => void;
  props: unknown;
  accountsDeactivated: boolean;
  reason: string;
  showMorePaymentMethodsSection: boolean;
  publicPaymentLink: string | undefined;
}

export interface AccountBalancePropsInterface {
  vaCurrency: string;
  isLoading: boolean;
  accountBalance: { data?: { USD?: { amount?: string; balance?: string } } };
  fetchAccountBalance: (currency: string) => void;
  createPayoutPending: () => void;
  createPayoutSuccess: () => void;
  fetchBeneficiaryDetailsSuccess: (data: { amount: string; reason: string }) => void;
  showNotification: (payload: { type: string; message: unknown }) => void;
  openModal: (payload: { size: string; component: JSX.Element }) => void;
  closeModal: () => void;
}

export type PurposeCodeIneligibleProps = {
  code?: string;
  onOpen: (payload: unknown) => void;
  onClose: () => void;
  onCloseAction: () => void;
};

export type MCCIneligibleProps = {
  error: string;
  onClose: () => void;
  onCloseAction: () => void;
};

export interface SuccessPopupProps {
  isOpen: boolean;
  account: string;
  shouldAllowEdit: boolean;
  publicPaymentLink: string | undefined;
  onDismiss: () => void;
  setPublicPaymentLink: (link: string) => void;
  showNotification: (payload: { type: string; message: unknown }) => void;
}
