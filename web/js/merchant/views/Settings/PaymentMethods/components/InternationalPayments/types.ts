export type InternationalPaymentsProps = {
  config: {
    isKycComplete: boolean;
    isWebsiteAdded: boolean;
    questionnaireStatus: {
      new_flow: boolean;
      enablement_progress: string;
      percentage_completion: number;
    };
    currentStatusOnHeader: string;
    isRequestAccessAllowed: boolean;
    isInternationalBlackList: boolean;
  };
  onRequestAccessClick: () => void;
};

export type ProductInfoProps = {
  title: string;
  status: 'rejected' | 'in_review' | 'no_action_received' | 'approved';
  product: string;
  transactionSize: number;
  settlementCycle: number;
  showStatusLabel: boolean;
  showRequestAccessBtn: boolean;
  onRequestAccessClick: () => void;
  questionnaireStatus?: {
    new_flow: boolean;
    enablement_progress: 'in_progress' | 'complete';
    percentage_completion: number;
  };
};

export type ActivationStatusProps = {
  status: string;
};
