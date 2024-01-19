import { ShowNotificationType } from 'common/typings';

export interface AllowlistRecords {
  country_code: string;
  zipcode: string;
  created_at: number;
}

export type CODEngineAllowlistUploadConfigs = {
  error: string | null;
  items: null | Array<Record<string, AllowlistRecords>>;
  isLoading: boolean;
  total_records: number;
};
export interface PaginationOptions {
  skip: number;
  count: number;
}

export type CODEngineAllowlistUploadProps = {
  openModal: (arg: Record<string, any>) => void;
  validateBatch: (file: File, progressTracker: Record<string, any>) => Promise<Record<string, any>>;
  codEngineAllowlistUpload: CODEngineAllowlistUploadConfigs;
  fetchList: (arg: Record<string, any>) => any;
  showNotification: ShowNotificationType;
  closeModal: () => void;
  deleteList: () => any;
  downloadList: () => void;
};

export type ReducerState = {
  codEngineAllowlistUpload: CODEngineAllowlistUploadConfigs;
};

export type AllowlistTableProps = {
  items: null | Array<Record<string, AllowlistRecords>>;
  isLoading: boolean;
  error: null | string;
  skip: string | number;
  count: string | number;
  paginate: (arg: PaginationOptions) => void;
  EmptyComponent: React.FC;
  hasMoreData: boolean;
};

export type SearchDataType = {
  zipcode: string | number;
  skip: number;
  count: number;
};

export type DeleteToolbarProps = {
  onDeleteClick: () => void;
};

export type FileUploadResponse = {
  data: {
    locations: Array<AllowlistRecords>;
    failed: number;
    count: number;
  };
};
