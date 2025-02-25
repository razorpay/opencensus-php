import type { ToastProps } from '@razorpay/blade/components';
import { DashboardNotificationInternal } from '.';

export type DashboardNotificationProps = Pick<
  ToastProps,
  'action' | 'autoDismiss' | 'color' | 'content' | 'duration' | 'leading'
> & {
  mode: ToastProps['type'];
  onDismissCallback: (notification: DashboardNotificationInternal) => void;
};
