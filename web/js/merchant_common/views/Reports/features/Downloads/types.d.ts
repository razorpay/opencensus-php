import { Location } from 'history';
import { InternalLogType } from 'merchant_common/views/Reports/types/log';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { OpenModalType, ShowNotificationType } from 'common/typings';

export interface AdditionalInformationType {
  currentMerchantId: string;
}

export interface DownloadsPropsType {
  location: Location;
  openModal: OpenModalType;
  logTableFilterType?: string;
  handleLogsTableFilterChange: (x: string) => void;
  dashboardType: DashboardType;
  isAllConfigLoaded: boolean;
}
export interface DownloadsTablePropsType {
  isLogsLoaded: boolean;
  logs: InternalLogType[];
  fixedHeaders?: boolean;
  fetchLogsSuccess: (x: { totalCount: number; logs: InternalLogType[] }) => void;
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
}
