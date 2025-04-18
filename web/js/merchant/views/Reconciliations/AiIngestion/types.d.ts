type AiIngestionStages = Record<'Add Sources' | 'Mapping' | 'Processing', boolean>;

type MlConfigKeys = 'sessionId' | 'auditLogId';

type MlConfigIds = Record<MlConfigKeys, string>;

interface Sources {
  id: string;
  name: string;
  fileUploadUrl: string | null;
  fileUploadPath: string | null;
  fileData: File | null;
  isUploaded: boolean;
}
interface SourceMappingProps {
  sourcesData: Sources[];
  processName: string;
  mlConfigIds: MlConfigIds;
  isGoBackClicked: boolean;
  setMlConfigIds: React.Dispatch<React.SetStateAction<MlConfigIds>>;
  setAiIngestionStages: React.Dispatch<React.SetStateAction<AiIngestionStages>>;
  showNotification: (notification: Notification) => void;
}

interface Notification {
  type: string;
  message: string;
}

interface UploadSourceAndPreviewProps {
  sourcesData: Sources[];
  processName: string;
  isEditingProcessName: boolean;
  setSourcesData: React.Dispatch<React.SetStateAction<Sources[]>>;
  setAiIngestionStages: React.Dispatch<React.SetStateAction<AiIngestionStages>>;
  showNotification: (notification: Notification) => void;
  setIsEditingProcessName: React.Dispatch<React.SetStateAction<boolean>>;
}

interface ReconStatsCardProps {
  title: string;
  value: string | null;
  subtitle: string | null;
  isLoading: boolean;
}

interface AiRunDashboardProps {
  mlConfigIds: MlConfigIds;
  setIsGoBackClicked: React.Dispatch<React.SetStateAction<boolean>>;
  setAiIngestionStages: React.Dispatch<React.SetStateAction<AiIngestionStages>>;
}

interface ColumnMappingTableProps {
  title: string;
  tableData: any[];
  columnConfig: any[];
  sourceColumnList: any;
  tableName: string;
  onAddRow: (args: { tableName: string }) => void;
  onDeleteRow: (args: { tableName: string; rowIndex: number }) => void;
  onChangeColumn: (args: {
    tableName: string;
    sourceId: string;
    index: number;
    values: any;
  }) => void;
}

export {
  Sources,
  SourceMappingProps,
  UploadSourceAndPreviewProps,
  AiIngestionStages,
  ReconStatsCardProps,
  AiRunDashboardProps,
  Notification,
  MlConfigIds,
  ColumnMappingTableProps,
};
