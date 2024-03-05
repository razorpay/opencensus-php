export type Notification = {
  type: 'success' | 'error' | 'info' | 'neutral';
  message: string | string[] | (() => string);
  closeTimeout?: number;
  className?: string;
  hidePrevious?: boolean;
};

export type ShowNotificationType = (arg0: Notification) => void;

export type HideNotificationType = (arg0: Notification) => void;

export type NotificationReducerState = {
  notifications: Notification[];
  hidePrevious?: boolean;
};
