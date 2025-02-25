import { DashboardHideNotificationType, DashboardNotificationInternal } from ".";

export type DashboardNotificationReducerState = {
    notifications: DashboardNotificationInternal[];
    hidePrevious?: boolean;
    hideNotification?: DashboardHideNotificationType;
  };
  