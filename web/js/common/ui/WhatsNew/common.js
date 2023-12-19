import moment from 'moment';

import { getItem } from 'common/utils/localStorage';
import { getAssetTrackingProperties } from 'merchant/models/GrowthService/commonUtils';
import store from 'merchant/store';

export const getExperimentVersion = () => {
  return 2.3;
};

export const getNotificationTrackingProperties = (notification, event_name = '') => {
  const oldTrackingData = (({
    version,
    version_description,
    target_metric,
    target_product_feature,
  }) => ({ version, version_description, target_metric, target_product_feature }))(
    notification || {},
  );

  return getAssetTrackingProperties(
    notification?.id,
    notification?.tracking_data,
    oldTrackingData,
    event_name,
  );
};

export const getNotificationsReadData = (merchant_id) => {
  const lastReadTS = getItem(`announcements-slider-${merchant_id}`) || 0;
  const notifications = [];
  const { announcements } = store.getState().growthService.announcements;

  notifications.push(...announcements);

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
  'primary-inverted': 'btn-outline',
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
