import React, { useEffect, isValidElement, useRef } from 'react';
import { useStore } from '@federated/apps/shell/commonStore';
import { ToastContainer, useToast } from '@razorpay/blade/components';
import {
  DashboardDeprecatedNotificationType,
  DashboardNotificationColor,
  DashboardNotificationInternal,
} from '@libs/shared-types';

export const NotificationContent = ({ message }) => {
  switch (true) {
    case typeof message === 'function':
      return message();
    case Array.isArray(message):
      return (
        <ul className="list-unstyled">
          {message.map((msg, idx) => (
            <li key={idx}>{msg}</li>
          ))}
        </ul>
      );
    case typeof message === 'string' || isValidElement(message):
      return message;
    default:
      return 'Something Went Wrong';
  }
};

const getNotificationType = (
  type: DashboardDeprecatedNotificationType,
): DashboardNotificationColor => {
  switch (type) {
    case 'error':
      return 'negative';
    case 'info':
      return 'information';
    case 'success':
      return 'positive';
    default:
      return type;
  }
};

const NotificationManager = () => {
  const { notifications } = useStore((state) => state.notifications);
  const hideNotification = useStore((state) => state.hideNotification);
  const toast = useToast();
  const prevNotificationsRef = useRef();

  const closeNotification = (notification: DashboardNotificationInternal) => {
    hideNotification?.(notification);
    notification?.onDismissCallback?.(notification);
  };

  const getDifferences = (prev, current) => {
    if (!prev) return current;
    return current.filter((item) => !prev.includes(item));
  };

  useEffect(() => {
    if (prevNotificationsRef.current !== notifications) {
      const triggeredNotification = getDifferences(
        prevNotificationsRef.current,
        notifications,
      )[0] as DashboardNotificationInternal;

      if (triggeredNotification) {
        toast.show({
          content: triggeredNotification.content || (
            <NotificationContent message={triggeredNotification.message} />
          ),
          color: triggeredNotification.color || getNotificationType(triggeredNotification.type!),
          autoDismiss: triggeredNotification.autoDismiss ?? true,
          duration: triggeredNotification.duration ?? triggeredNotification.closeTimeout,
          onDismissButtonClick: () => closeNotification(triggeredNotification),
          id: triggeredNotification.id.toString(),
          type: 'informational',
        });
      }

      // Update the previous notifications ref
      prevNotificationsRef.current = notifications;
    }
  }, [notifications]);

  return (
    <>
      <ToastContainer />
    </>
  );
};

export default NotificationManager;
