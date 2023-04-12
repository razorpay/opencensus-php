import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { InternalLogType } from 'merchant_common/views/Reports/types/log';

export interface DownloadsState {
  loading: boolean;
  filter: string;
  pageTrack: number;
  logs: Record<string, InternalLogType>;
  totalCount: number | undefined | null;
  genericPoll: boolean;
}

export interface InitialStateType {
  overview: {
    reportConfigs: {
      allConfigs: {
        loading: boolean;
        data: BaseConfigType[];
        error: boolean;
      };
      recentConfigs: {
        loading: boolean;
        data: BaseConfigType[];
        error: boolean;
      };
    };
  };
  downloads: DownloadsState;
}

export interface ReportsInitialStateType {
  partner: InitialStateType;
  merchant: InitialStateType;
  linkedAccount: InitialStateType;
}
