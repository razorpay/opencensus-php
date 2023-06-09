import { BaseLogType } from 'merchant_common/views/Reports/types/log';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';

export interface SchedulesPropsType {
  openModal: (x: { component: JSX.Element; size: string }) => void;
  dashboardType: DashboardType;
  isAllConfigLoaded: boolean;
  params: {
    scheduleData: ScheduleType;
  };
}
export interface RunHistoryTablePropsType {
  dashboardType: DashboardType;
  showNotification: (x: any) => void;
  scheduleId: string;
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
