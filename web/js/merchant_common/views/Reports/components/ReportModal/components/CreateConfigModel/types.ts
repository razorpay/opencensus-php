export type FilterOperationType =
  | 'IN'
  | 'IS NULL'
  | 'IS NOT NULL'
  | 'NOT IN'
  | '<'
  | '>'
  | 'BETWEEN'
  | 'DYNAMIC FILTER';

export type FilterType = {
  op: FilterOperationType[];
  values?: (number | string)[];
};

export type AvailableColumnsType = {
  fields: Record<string, string[]>;
  filters: Record<string, Record<string, FilterType>>;
  scopes?: string[];
};

export type TableNode<T> = {
  id: T;
  value: T;
};

export type BaseReportType = {
  name: string;
  type: string;
  type_title?: string;
  source?: string;
  fields_map?: Record<string, string[]>;
};

export type CustomReportConfigType = {
  type?: string;
  name: string;
  description: string;
  template: {
    output_fields: string[];
    fields_map: Record<string, string[]>;
  };
  source?: string;
};

export type CustomReportType = {
  id: string;
  type: string;
  name: string;
  description: string;
  template: {
    fields_map: Record<string, string[]>;
    output_fields: string[];
  };
  pipeline_params: unknown;
  emails: string[];
  created_by: string;
  status: unknown;
  source: string;
  created_at: number;
  updated_at: number;
  sftp_job_name?: unknown;
  feature_names?: string[];
  query_meta?: unknown;
  type_title?: string;
  alert_info?: unknown;
  files?: string[];
  deleted_at?: number;
};

export type CreatePayloadType = {
  baseReport: BaseReportType;
  reportName: string;
  reportDescription: string;
  renamedColumns: Record<string, string>;
  displayColumns: TableNode<string>[];
  isEditOrClone?: string;
};

export type EditConfigPayloadType = {
  setConfigsFetchTrigger: () => void;
  handleClose: () => void;
  isEditOrClone?: string;
  configId?: string;
  setIsCreatingConfig: (isCreatingConfig: boolean) => void;
};

export type CreateOrCloneConfigPayloadType = {
  setConfigsFetchTrigger: () => void;
  handleClose: () => void;
  setIsCreatingConfig: (isCreatingConfig: boolean) => void;
  isEditOrClone?: string;
};

export type ConfirmModalType = {
  isOpenExitPromptModal: boolean;
  setIsOpenExitPromptModal: (exitPromptModal: boolean) => void;
  handleClose?: () => void;
  title: string;
  description: string;
  isDeleteConfig?: boolean;
  configId?: string;
};

export type HandleConfigDeleteType = {
  configId: string;
  setIsOpenExitPromptModal: (exitPromptModal: boolean) => void;
  setIsDeletingConfig: (setIsDeletingConfig: boolean) => void;
  setConfigsFetchTrigger: () => void;
};

export type CreateConfigModalType = {
  isOpen: boolean;
  setIsOpen: (isOpen: boolean) => void;
  isEditOrClone?: string;
  id?: string;
};

export type HandleValidationType = {
  progressFormInView: number;
  setProgressFormInView: (progressFormInView: number) => void;
  isEditOrClone?: string;
  setIsLoading: (isLoading: boolean) => void;
};

export enum Action {
  Edit = 'Edit',
  Clone = 'Clone',
  Create = 'Create',
}
