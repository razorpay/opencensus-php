export type BeneficiaryDetailType = {
  accountNumber: string;
  beneficiaryName: string;
  bankName: string;
  bicSwift: string;
  fee: string;
  amount?: string;
  reason?: string;
};

export type GlobalBankWithdrawModalProps = {
  balance: string;
  onClose: () => void;
  onSubmit: ({ amount, reason }: { amount: string; reason: string }) => void;
  currency: string;
} & {
  isLoading: boolean;
  data: BeneficiaryDetailType;
  error: boolean;
  showNotification: (payload: { type: 'error' | 'success'; message: string }) => void;
  fetchBeneficiaryDetails: () => Promise<{
    data: {
      account_number: string;
      name: string;
      bank_name: string;
      bic_swift: string;
      commission_fee: string;
    };
    success: number;
  }>;
  fetchBeneficiaryDetailsPending: () => void;
  fetchBeneficiaryDetailsSuccess: (data: BeneficiaryDetailType | null) => void;
  fetchBeneficiaryDetailsError: () => void;
};

export type ConfirmWithdrawalModalProps = {
  amount: string;
  fee: string;
  isLoading: boolean;
  onClose: () => void;
  onSubmit: () => void;
};

export type StatusModalProps = {
  amount: string | undefined;
  type: 'error' | 'success';
  onClose: () => void;
  onTryAgain: () => void;
};
