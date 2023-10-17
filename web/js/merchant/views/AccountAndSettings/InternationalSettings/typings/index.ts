/* Type Start -----> */

export type PopupDataType = {
  isOpen: boolean;
  type: string;
  month: string;
  year: number;
};

export type FirsFileType = {
  id: string;
  document_type: string;
  merchant_id: string;
  file_store_id: string | null;
  file_status: string;
};

export type MonthlyDataType = {
  [month: string]: Array<FirsFileType>;
};

export type FirsDataType = {
  [year: string]: MonthlyDataType;
};

export type FirsContextType = {
  listYear: number;
  setListYear: React.Dispatch<React.SetStateAction<number>>;
  firsData: FirsDataType;
  setFirsData: React.Dispatch<React.SetStateAction<FirsDataType>>;
  getFirsData: (year: number, month?: string, onSuccess?: () => void) => Promise<void>;
  isLoading: boolean;
  setIsLoading: React.Dispatch<React.SetStateAction<boolean>>;
  error: string | null;
  setError: React.Dispatch<React.SetStateAction<string | null>>;
  popupData: PopupDataType;
  setPopupData: React.Dispatch<React.SetStateAction<PopupDataType>>;
  isRequestFirsEnabled: boolean;
};

export type ApiResponsetype = {
  data?: unknown;
  success: boolean;
};

export type GetCurrentDateInfoType = {
  currentDate: Date;
  currentYear: number;
  currentMonth: number;
};

export type CheckFirsDataReturnType = Array<FirsFileType> | MonthlyDataType | null;

export type DropdownListType = Array<{ title: string; value: string }>;

export type GetFirsDetailsReturnType = {
  fileCount: number;
  hasTransactions: boolean;
  isFirsRequested: boolean;
};

export type GetCategorizedFirsFilesType = {
  internalFirs: Array<FirsFileType>;
  bankFirs: Array<FirsFileType>;
  shouldShowRequestButton: boolean;
  isFirsRequestFailed: boolean;
};

/* Type End -----> */

/* Interface Start ----> */

export interface FirsTablePropsT {
  showNotification: (data: unknown) => void;
}

export interface FilePropsT {
  file: FirsFileType;
  month: string;
  year: number;
}

export interface ActionPopupPropsT {
  popupType: string;
}

/* Interface End ----> */
