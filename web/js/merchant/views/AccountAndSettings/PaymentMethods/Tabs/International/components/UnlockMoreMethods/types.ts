import { WorkflowStates } from 'common/constant/enums';
import { User } from 'common/typings';
import { REDUCER_INITIAL_STATE } from 'merchant/reducers/unlockIntlPaymentMethods/initialState';
import { MerchantICProductStatus } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';

type ProductPaCbWorkflow = {
  loading: boolean;
  needs_clarification?: string | null;
  request_under_validation?: boolean;
  tags?: string[];
  workflow_exists?: boolean;
  workflow_status?: WorkflowStates;
  workflow_created_at?: number;
  rejection_reason_message?: string;
  workflow_rejected_at?: number;
};

export type UnlockMoreMethodsProps = REDUCER_INITIAL_STATE & {
  instrument: {
    listDescription: string;
    header: string;
    slug: string;
  };
  user: User;
  productPaCbStatus: MerchantICProductStatus['products_pa_cb'] | undefined;
  isInstantBankTransferActivated: boolean;
  isMoneySaverAccountsActivated: boolean;
  fetchEddDetails: (workflowStatus: string | undefined) => fetchEddDetailsResponse;
  setIsMethodEnablementFormOpen: (data: { isOpen: boolean; defaultTab?: number }) => void;
  setKycDocumentStatus: (status: string) => void;
  openModal: (payload: { size: string; component: JSX.Element }) => void;
  showNotification: (payload: { type: string; message: string }) => void;
};

export type TimelineViewProps = REDUCER_INITIAL_STATE & {
  productPaCbStatus: MerchantICProductStatus['products_pa_cb'];
  productPaCbWorkflow: ProductPaCbWorkflow;
  user: User;
  onLoadEddDetails: () => void;
  setIsMethodEnablementFormOpen: (data: { isOpen: boolean; defaultTab?: number }) => void;
};

export type fetchEddDetailsResponse = {
  error?: {
    message: string;
  };
  payload?: {
    vKycStatus: string;
  };
};
