interface Notification {
  type: string;
  message: string;
}

interface Column {
  merchant_process_id: string;
  merchant_source_id: string;
  name: string;
  value?: string;
  merchant_process_name: string;
  merchant_source_name: string;
  id: string;
}

interface SelectedColumn extends Column {
  editedName?: string;
}

type SelectColumnType = 'edit' | 'create';

interface SelectColumnCheckboxesProps {
  columns: Column[];
  selectedColumnForReport: SelectedColumn[];
  setSelectedColumnForReport: React.Dispatch<React.SetStateAction<SelectedColumn[]>>;
  sourceId: string;
  searchQuery: string;
  showNotification: (notification: Notification) => void;
  columnList: Column[];
  selectedColumnCheckbox: string[];
  setSelectedColumnCheckBox: React.Dispatch<React.SetStateAction<Record<string, string[]>>>;
}

interface Source {
  id: string;
  name: string;
  columns: Column[];
}

interface MerchantProcessLevel {
  id: string;
  name: string;
  sources: Record<string, Source>;
}

interface MerchantSourcesColumn {
  [processId: string]: MerchantProcessLevel;
}

interface SelectColumnsProps {
  columnList: Column[];
  merchantSourcesColumn: MerchantSourcesColumn;
  isLoadingForColumnsForMatchingFields: boolean;
  isErrorForColumnsForMatchingFields: boolean;
  selectedColumnForReport: SelectedColumn[];
  setSelectedColumnForReport: React.Dispatch<React.SetStateAction<SelectedColumn[]>>;
  isLoadingForCreateReport: boolean;
  updateReportIsLoading: boolean;
  setHasCompletedCreateReportStep: React.Dispatch<React.SetStateAction<CompletedAccordionStepType>>;
  configForm: ConfigForm;
  setConfigForm: React.Dispatch<React.SetStateAction<ConfigForm>>;
  showNotification: (notification: Notification) => void;
  selectedColumnCheckbox: Record<string, string[]>;
  setSelectedColumnCheckBox: React.Dispatch<React.SetStateAction<Record<string, string[]>>>;
  type: SelectColumnType;
}

type ConfigForm = Record<'name' | 'description', string>;

interface SaveConfigModalProps {
  isOpenSaveConfigModal: boolean;
  setIsOpenSaveConfigModal: (isOpen: boolean) => void;
  configForm: ConfigForm;
  setConfigForm: React.Dispatch<React.SetStateAction<ConfigForm>>;
  setHasCompletedCreateReportStep: React.Dispatch<React.SetStateAction<{ selectColumns: boolean }>>;
  showNotification: (notification: Notification) => void;
}

interface MerchantProcess {
  merchant_process_id: string;
  merchant_process_name: string;
  merchant_source_ids: string[];
}

interface ReportConfig {
  id: string;
  name: string;
  merchant_processes: MerchantProcess[];
  last_report_run_time: number;
  config_created_at: number;
}

interface FetchReportListConfigsResponse {
  status_code: number;
  success: boolean;
  data: ReportConfig[];
}

interface MerchantSource {
  id: string;
  name: string;
}

interface ProcessItem {
  id: string;
  name: string;
  merchant_sources: MerchantSource[];
}

interface ProcessesData {
  data: {
    items: ProcessItem[];
  };
}

interface ProcessSelectionModalProps {
  openProcessSelectionModal: boolean;
  selectedProcessesItem: ProcessItem[];
  setSelectedProcessesItem: React.Dispatch<React.SetStateAction<ProcessItem[]>>;
  setIsProcessesSelectionDone: React.Dispatch<React.SetStateAction<boolean>>;
  setOpenProcessSelectionModal: React.Dispatch<React.SetStateAction<boolean>>;
  showNotification: (notification: Notification) => void;
}

interface Process {
  id: string;
  name: string;
  columns: Column[];
}

interface JoiningColumn {
  column: string;
  merchant_source_id: string;
}

interface MerchantProcessConfig {
  merchant_process_id: string;
  joining_columns: JoiningColumn[];
}

interface FrontendJoiningField {
  merchant_process_id: string;
  merchant_source_id: string;
  column: string;
  merchant_process_name: string;
  merchant_source_name: string;
}

interface JoiningConfig {
  merchant_process_config: MerchantProcessConfig[];
}

interface FrontendJoiningConfig {
  matching_fields: FrontendJoiningField[];
}

interface JoiningConfigdata {
  data: {
    joining_config: JoiningConfig[];
    frontend_joining_config: FrontendJoiningConfig[];
  };
}

interface MatchingFieldKeySelectionProps {
  processes: Record<string, Process>;
  isLoadingForColumnsForMatchingFields: boolean;
  isErrorForColumnsForMatchingFields: boolean;
  showNotification: (notification: Notification) => void;
  setHasCompletedCreateReportStep: React.Dispatch<
    React.SetStateAction<{ matchingFields: boolean }>
  >;
  matchingFieldKeys: Record<string, Process>[];
  joiningConfigData: JoiningConfigdata;
  setMatchingFieldKeys: React.Dispatch<React.SetStateAction<Record<string, Process>[]>>;
  joiningConfigMutation: () => void;
  isSuccessForJoiningConfig: boolean;
  isLoadingForJoiningConfig: boolean;
}

interface SelectedColumn extends Column {
  editedName?: string;
}

interface EditColumnModalProps {
  isOpenEditNameModal: boolean;
  setOpenEditNameModal: (isOpen: boolean) => void;
  editColumn: SelectedColumn | null;
  updatedNameForColumn: string;
  setUpdatedNameForColumn: React.Dispatch<React.SetStateAction<string>>;
  renameSelectedColumnForReport: (payload: { columnId: string; editedName: string }) => void;
}

interface ReportConfig {
  id: string;
  name: string;
  merchant_processes: MerchantProcess[];
  last_report_run_time: number;
  config_created_at: number;
}

interface DownloadReportModalProps {
  downloadReportConfig: ReportConfig | null;
  isOpenDownloadModal: boolean;
  setIsOpenDownloadModal: (isOpen: boolean) => void;
}

interface ReportItem {
  id: string;
  merchant_id: string;
  config_name: string;
  config_id: string;
  emails: string[] | null;
  status: string;
  format: string;
  file_name: string;
  trigger_type: string;
  report_path: string;
  created_at: number;
  updated_at: number;
}

interface FetchDownloadListResponse {
  data: {
    total_count: number;
    items: ReportItem[];
    first_id: string;
    last_id: string;
    has_more: boolean;
  };
}

interface DownloadFileResponse {
  data: {
    signed_url: string;
  };
}

interface MerchantSource {
  id: string;
  name: string;
  allow_upload: boolean;
}

interface SelectedProcessItem {
  id: string;
  name: string;
  product_id: string;
  product_name: string;
  last_run: number;
  type: string;
  merchant_sources: MerchantSource[];
}

type ReportType = 'sourceData' | 'matchingFields' | 'selectColumns';

type CompletedAccordionStepType = Record<ReportType, boolean>;

type AccordionExpandedIndexType = Record<ReportType, number>;

interface ConfigProcessSelectionProp {
  selectedProcessesItem: SelectedProcessItem[];
  isProcessesSelectionDone: boolean;
  setOpenProcessSelectionModal: React.Dispatch<React.SetStateAction<boolean>>;
  removeProcessCardHandler: (params: { processId: string }) => void;
  setHasCompletedCreateReportStep: React.Dispatch<React.SetStateAction<CompletedAccordionStepType>>;
}

interface ColumnSourceLevel {
  id: string;
  name: string;
  columns: SelectedColumn[];
}

interface SourceColumnProcess {
  id: string;
  name: string;
  sources: Record<string, ColumnSourceLevel>;
}

type ColumnSourceObjType = Record<string, SourceColumnProcess>;

interface EditReportProps {
  showNotification: (notification: Notification) => void;
}

interface CreateReportProps {
  showNotification: (notification: Notification) => void;
}

export {
  SaveConfigModalProps,
  FetchReportListConfigsResponse,
  ReportConfig,
  ProcessSelectionModalProps,
  ProcessesData,
  MatchingFieldKeySelectionProps,
  Process,
  EditColumnModalProps,
  DownloadReportModalProps,
  ReportItem,
  FetchDownloadListResponse,
  DownloadFileResponse,
  ConfigProcessSelectionProp,
  SelectColumnsProps,
  SelectColumnCheckboxesProps,
  SelectedColumn,
  ConfigForm,
  CompletedAccordionStepType,
  AccordionExpandedIndexType,
  ColumnSourceObjType,
  EditReportProps,
  CreateReportProps,
  SelectedProcessItem,
};
