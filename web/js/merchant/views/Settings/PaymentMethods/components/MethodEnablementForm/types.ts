import { ModalProps } from '@razorpay/blade/components';
import { FormikContextType } from 'formik';

import { User } from 'common/typings';
import { REDUCER_INITIAL_STATE } from 'merchant/reducers/unlockIntlPaymentMethods/initialState';
import { V_KYC_STATUS } from 'merchant/reducers/videoKYCBanner';
import { ICProductStates } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';

export interface BulletPointsProps {
  title: string;
  description: string;
}
export type ModalContainerProps = {
  user: User & {
    promoter_pan_name?: string;
  };
  isOpen: ModalProps['isOpen'];
  defaultTab: number;
  vKycStatus: (typeof V_KYC_STATUS)[keyof typeof V_KYC_STATUS] | null;
  kycDocumentStatus: (typeof ICProductStates)[keyof typeof ICProductStates] | null;
  onDismiss: ModalProps['onDismiss'];
  showNotification: (showNotification: { type: string; message: string }) => void;
  createVCipLink: VideoKycProps['createVCipLink'];
  setIsMethodEnablementFormOpen: (data: { isOpen: boolean; defaultTab?: number }) => void;
  setKycDocumentStatus: (status: string) => void;
};

export type VideoKycProps = REDUCER_INITIAL_STATE & {
  promoterPanName: string | undefined;
  createVCipLink: (name: string | undefined) => VcipLinkGenerationResponse;
  showNotification: (showNotification: { type: string; message: string }) => void;
};

export type FormWrapperProps = ModalContainerProps & {
  user: User;
  data: unknown;
};

export type MethodEnablementFormProps = {
  isOpen: boolean;
  onDismiss: () => void;
};

export type FormikValues = {
  kyc_tnc_accepted: boolean;
  documents: Record<string, { display_name: string; id: string }[] | null>;
  signatory?: null | string;
  vkyc_tnc_accepted: boolean;
};

export type ApiDataType = {
  data?: unknown;
  accepts_intl_txns?: boolean | number;
  documents?: Record<string, { display_name: string; id: string }[] | null>;
  products?: Array<string>;
  business_use_case?: string;
  goods_type?: string;
  existing_risk_checks?: Array<string | null>;
};

export type IntlFormDataType = {
  submitted_at?: string;
  updated_at?: string;
  created_at?: string;
};

export type FormContextType = {
  selectedTab: number;
  onTabClick: (index: number) => void;
  initialValues: { [x: string]: unknown };
  setInitialValues: React.Dispatch<React.SetStateAction<{ [x: string]: any }>>;
  isLoading: boolean;
  setIsLoading: React.Dispatch<React.SetStateAction<boolean>>;
  apiData: ApiDataType | null;
  setApiData: React.Dispatch<React.SetStateAction<ApiDataType | null>>;
  alert: string;
  setAlert: React.Dispatch<React.SetStateAction<string>>;
};

export type AdditionalDocumentsProps = {
  user: User;
  disabled?: boolean;
  saveFormData: (values: FormikContextType<FormikValues>, skipDirtyCheck?: boolean) => void;
  showNotification: (showNotification: { type: string; message: string }) => void;
};

export type FileDropBoxProps = {
  doc: Doc;
  index: number;
};

export type Doc = {
  label: string;
  name: string;
  type: string;
  info?: string;
  tooltip?: string;
  isRequired?: boolean;
  subLabel?: string;
  options?: Doc[];
};

export type UseAdditionalDocumentsReturn = {
  docs: Doc[];
  selectedDocument: Doc[];
  selectedOption: string[];
  formikDocuments: Record<string, { display_name: string; id: string }[] | null>;
  handleSelect: (value: string | undefined, index: number) => void;
  handleUploadFile: (
    docType,
    file,
    progressTracker,
  ) => Promise<
    | void
    | {
        id: string;
        display_name: string;
      }
    | undefined
  >;
  handleFileRemove: (docId: string, docType: string) => void;
  filterOtherSelectOptions: (
    index: number,
    options: { label: string; name: string; tooltip?: string }[] | undefined,
  ) => { label: string; name: string; tooltip?: string }[];
};

export type VcipLinkGenerationResponse = {
  payload?: {
    details?: {
      weblink?: string;
    };
  };
  error?: {
    message?: string;
  };
};
