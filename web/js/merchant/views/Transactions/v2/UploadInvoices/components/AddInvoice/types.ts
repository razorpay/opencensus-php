import { Item } from 'merchant/views/Transactions/v2/UploadInvoices/types';
import { FILE_TYPE, ONBOARDING_PARTNERS, ONBOARDING_STATUS } from './constants';

export type Partner = typeof ONBOARDING_PARTNERS[keyof typeof ONBOARDING_PARTNERS];
export type Status = typeof ONBOARDING_STATUS[keyof typeof ONBOARDING_STATUS];
export type FileType = typeof FILE_TYPE[keyof typeof FILE_TYPE];

export type AddInvoiceType = {
  id: Item['id'];
  isOpen: boolean;
  onDismiss: () => void;
  showNotification: (args: { type: string; message: string }) => void;
  onUploadSuccess: (exportInvoiceId: string) => void;
};

export type UploadInvoiceType = {
  onUpload: (file: File) => void;
  onRemove: () => void;
  showNotification: AddInvoiceType['showNotification'];
  file: File | null;
};

export type FetchInvoiceType = UploadInvoiceType;

export type FetchOnboardingStatusResponse =
  | Array<{
      name: Partner;
      status: Status;
    }>
  | undefined;

export type GetPartnerDetails = Array<{ name: Partner; status: Status } | undefined>;

export type GetOnboardingDetails = {
  headerText: string;
  buttonText: string;
  partner: Partner;
  status: Status | undefined;
};
