import { Location } from 'history';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';

export interface AdditionalInformationType {
  currentMerchantId?: string;
  onViewActivityOpen: () => void;
  openModal: (x: { component: JSX.Element; size: string }) => void;
}

export interface SchedulesPropsType {
  location: Location;
  openModal: (x: { component: JSX.Element; size: string }) => void;
  scheduleFilter?: string;
  handleScheduleFilter: (x: string) => void;
  dashboardType: DashboardType;
  isAllConfigLoaded: boolean;
}
export interface SchedulesTablePropsType {
  isSchedulesLoaded: boolean;
  allSchedules: ScheduleType[];
  fixedHeaders?: boolean;
  fetchSchedulesSuccess: (x: { totalCount: number; allSchedules: ScheduleType[] }) => void;
  handleSchedulesPageTrack: (x: number) => void;
  pageTrack: number;
  filter: string;
  additionalInfo: AdditionalInformationType;
  genericPoll: boolean;
  stopSchedulePoll: () => void;
  startSchedulePoll: () => void;
  totalCount: number;
  dashboardType: DashboardType;
  showNotification: (x: any) => void;
  openModal: (x: { component: JSX.Element; size: string }) => void;
}
