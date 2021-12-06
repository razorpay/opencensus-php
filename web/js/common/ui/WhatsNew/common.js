import moment from 'moment';
import { getItem } from 'common/utils/localStorage';
import store from 'merchant/store';
import { getAssetTrackingProperties } from '../../../merchant/models/GrowthService/commonUtils';

export const getExperimentVersion = (user) => {
  if (user.isAnnouncementTextEnabled) return 2.1;
  if (user.isWhatsNewTextEnabled) return 2.2;

  return 2.3;
};

export const getNotificationTrackingProperties = (notification) => {
  const oldTrackingData = (({
    version,
    version_description,
    target_metric,
    target_product_feature,
  }) => ({ version, version_description, target_metric, target_product_feature }))(notification);

  return getAssetTrackingProperties(notification.id, notification.tracking_data, oldTrackingData);
};

export const getNotificationsReadData = (merchant_id) => {
  const lastReadTS = getItem(`announcements-slider-${merchant_id}`) || 0;
  const notifications = [];
  const user = store.getState().session.user;
  const { announcements } = store.getState().growthService.announcements;

  if (user.isGrowthServiceEnabled) {
    notifications.push(...announcements);
  } else {
    if (window.old_notifications) notifications.push(...window.old_notifications);
    if (window.new_notifications) notifications.push(...window.new_notifications);
    notifications.sort((first, second) => second?.start_ts - first?.start_ts);
  }

  const ID = [];
  const readID = [];
  const unreadID = [];

  let totalUnread = 0;
  for (let i = 0; i < notifications.length; i++) {
    const notifStartTS = notifications[i]?.start_ts;
    const notifEndTS = notifications[i]?.end_ts;
    const notifID = notifications[i]?.id;

    if (notifID) ID.push(getNotificationTrackingProperties(notifications[i]));
    if (lastReadTS < notifStartTS && moment().unix() < notifEndTS) {
      totalUnread++;

      if (notifID) unreadID.push(getNotificationTrackingProperties(notifications[i]));
    } else if (notifID) readID.push(getNotificationTrackingProperties(notifications[i]));
  }

  return {
    ID,
    readID,
    unreadID,
    totalUnread,
  };
};

const BUTTON_CLASSES = {
  button: 'btn-primary',
  'primary-inverted': 'btn-primary--invert',
};

export const getButtonClass = (type) => {
  return !!BUTTON_CLASSES[type] ? BUTTON_CLASSES[type] : 'btn-link';
};

export const iconMap = {
  transactions: 'i-repeat',
  settlements: 'i-done-all',
  paymentpages: 'i-payment-pages',
  invoices: 'i-notes',
  paymentlinks: 'i-link',
  marketplace: 'i-store',
  subscription: 'i-refresh',
  smartcollect: 'i-account-balance',
  reports: 'i-books',
};

export const getQueryData = (param, user) => {
  switch (param) {
    case 'mid': {
      const merchant = user.merchants[user.current];

      return merchant.id;
    }
    case 'business_name': {
      return user.business_name;
    }
    case 'email': {
      return user.email;
    }
    default: {
      return null;
    }
  }
};
