import { OpenModalType, ShowNotificationType } from 'common/typings';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';

export interface ControlActionsArgs {
  openModal: OpenModalType;
  scheduleData: ScheduleType;
  showNotification: ShowNotificationType;
  stopSchedulePoll: () => void;
  startSchedulePoll: () => void;
  dashboardType: DashboardType;
}
