import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';
import { ResPayload } from 'merchant_common/views/Reports/api/types';

export interface SchedulesPropsType {
  openModal: (x: { component: JSX.Element; size: string }) => void;
  dashboardType: DashboardType;
  isAllConfigLoaded: boolean;
  params: {
    scheduleData: ScheduleType;
  };
}

export interface FilterType {
  label: string;
  value: string;
}

export interface ScheduleRunHistoryContextType {
  filter: FilterType;
  pageTrack: number;
  isPollActive: boolean;
  isLoading: boolean;
  logs: BaseLogType[];
  totalLogsCount: number;
}

export interface UseRunHistoryReducerHookReturnType extends ScheduleRunHistoryContextType {
  handlePageTrack: (x: number) => void;
  handleLogsFetchSuccess: (x: ResPayload<BaseLogType>) => void;
  setIsPollActive: (x: boolean) => void;
  handleLogsFilter: (x: FilterType) => void;
}

export interface RunHistoryTablePropsType {
  dashboardType: DashboardType;
  showNotification: (x: any) => void;
  scheduleId: string;
  logsHistoryReducer: UseRunHistoryReducerHookReturnType;
}
