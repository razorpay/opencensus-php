import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { OpenModalType, ShowNotificationType } from 'common/typings';

export interface AdditionalInformationType<ActionType> {
  currentMerchantId?: string;
  trackDownloadFile: ({
    actionName,
    properties,
    dashboardType,
  }: {
    actionName: ActionType;
    properties?: Record<string, unknown> | undefined;
    dashboardType: DashboardType;
  }) => void;
}

export interface DownloadsPropsType {
  openModal: OpenModalType;
  logTableFilterType?: string;
  handleLogsTableFilterChange: (x: string) => void;
  dashboardType: DashboardType;
  isAllConfigLoaded: boolean;
  headers: Record<string, string>;
}
export interface DownloadsTablePropsType {
  isLogsLoaded: boolean;
  logs: BaseLogType[];
  fixedHeaders?: boolean;
  fetchLogsSuccess: (x: { totalCount: number; logs: BaseLogType[] }) => void;
  handlePageChange: (x: number) => void;
  pageTrack: number;
  filter: string;
  additionalInfo: AdditionalInformationType;
  genericPoll: boolean;
  stopLogsPoll: () => void;
  startLogsPoll: () => void;
  totalCount: number;
  dashboardType: DashboardType;
  showNotification: ShowNotificationType;
  headers: Record<string, string>;
}
