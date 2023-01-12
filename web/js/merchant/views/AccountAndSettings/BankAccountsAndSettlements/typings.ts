import User from 'merchant/models/User';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type AnyObject = Record<string, any>;

export interface BankAccountDetailsContainerProps {
  fetchBankAccount: () => void;
  fetchSettlementAmount: () => void;
  user: User;
  fetchBankAccountChangeStatus: (userId: string) => Promise<AnyObject>;
  profile: { bankAccount?: AnyObject };
  settlement_amount: { data: AnyObject };
  openModal: (data: AnyObject) => void;
  closeModal: () => void;
  showNotification: (data: AnyObject) => void;
  saveBankAccountChangesAutomate: (userId: string, formData: FormData) => Promise<AnyObject>;
  fetchWorkflowStatus: (worklowName: string) => void;
  saveBankAccountChanges: (userId: string, formData: FormData) => Promise<AnyObject>;
}
