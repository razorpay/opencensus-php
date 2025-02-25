import {
  PaymentsDashboardAppReducerState,
  PaymentsDashboardSessionReducerState,
} from '@libs/shared-types/payments';
import { LADashboardAppReducerState, LADashboardSessionReducerState } from '@libs/shared-types/la';

import {
  DashboardOpenModalType,
  DashboardCloseModalType,
  DashboardShowNotificationType,
  DashboardHideNotificationType,
  DashboardModalReducerState,
  DashboardNotificationReducerState,
} from '@libs/shared-types';

export type Store = {
  session: LADashboardSessionReducerState | PaymentsDashboardSessionReducerState;
  app: LADashboardAppReducerState | PaymentsDashboardAppReducerState;
  notifications: DashboardNotificationReducerState;
  modal: DashboardModalReducerState;
  showNotification: DashboardShowNotificationType;
  hideNotification: DashboardHideNotificationType;
  openModal: DashboardOpenModalType;
  closeModal: DashboardCloseModalType;
};
