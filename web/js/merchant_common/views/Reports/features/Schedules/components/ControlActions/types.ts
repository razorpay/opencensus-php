import { IconProps, IconComponent } from '@razorpay/blade/components';
import { OpenModalType, ShowNotificationType } from 'common/typings';
import { ScheduleAPIFnParams } from 'merchant_common/views/Reports/api/types';
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

export enum ControlActions {
  'pause' = 'pause',
  'delete' = 'delete',
  'resume' = 'resume',
}

export type ControlActionsType = keyof typeof ControlActions;

export interface HandleControlActionFnParam {
  action: ControlActionsType;
  icon: ((x: IconProps) => React.ReactElement) | IconComponent;
  promise: (x: ScheduleAPIFnParams) => Promise<void>;
}
