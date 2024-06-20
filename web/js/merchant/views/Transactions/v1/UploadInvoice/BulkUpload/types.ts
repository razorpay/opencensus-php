import { User } from 'common/typings';

export type UploadConfigType = {
  batchSize: number;
  retries: number;
  maxFiles: number;
};

export type ModalContextType = {
  currentTab: number;
  setCurrentTab: React.Dispatch<React.SetStateAction<number>>;
  isUploading: boolean;
  setIsUploading: React.Dispatch<React.SetStateAction<boolean>>;
  files: Array<File>;
  setFiles: React.Dispatch<React.SetStateAction<Array<File>>>;
  progress: number;
  setProgress: React.Dispatch<React.SetStateAction<number>>;
  clientErrors: Array<BatchError>;
  setClientErrors: React.Dispatch<React.SetStateAction<Array<BatchError>>>;
  shouldShowExitPrompt: boolean;
  setShouldShowExitPrompt: React.Dispatch<React.SetStateAction<boolean>>;
};

export type BatchError = {
  fileName: string;
  size: number;
  message: string;
};

export type BulkUploadResponse = {
  status: string;
  reason?: {
    errors?: string;
  };
};

export type TransfromDroppedFilesType = {
  transformedFiles: Array<File>;
  clientErrors: Array<BatchError>;
};

export interface UploadTabProps {
  tabType?: string;
  tabDescription?: string;
  showLoader?: boolean;
  progressMessage?: string;
  progressPercent?: number;
  invoiceCount?: number;
  errors?: Array<BatchError>;
}

export interface BulkUploadModalProps {
  refreshList: () => void;
  purposeCode: string;
}
export interface DropScreenProps {
  showNotification: React.Dispatch<React.SetStateAction<unknown>>;
}

export interface UploadResultProps {
  refreshList: () => void;
  user: User;
}

export interface ModalContainerProps {
  purposeCode: string;
  closeModal: React.Dispatch<React.SetStateAction<void>>;
  showNotification: React.Dispatch<React.SetStateAction<unknown>>;
  refreshList: () => void;
}

export interface ModalProviderInterface {
  children: JSX.Element;
}

export interface ExitConfirmationInterface {
  closeModal: React.Dispatch<React.SetStateAction<void>>;
}
