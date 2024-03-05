import type {
  AppReducerState,
  SessionReducerState,
  ModalReducerState,
  OpenModalType,
  CloseModalType,
  NotificationReducerState,
  ShowNotificationType,
  HideNotificationType,
} from 'common/typings';

export type Store = {
  session: SessionReducerState;
  app: AppReducerState;
  notifications: NotificationReducerState;
  modal: ModalReducerState;
  showNotification: ShowNotificationType;
  hideNotification: HideNotificationType;
  openModal: OpenModalType;
  closeModal: CloseModalType;
};
