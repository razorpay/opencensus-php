import moment from 'moment';
import { getItem } from 'common/utils/localStorage';
import store from 'merchant/store';

export const getExperimentVersion = (user) => {
  if (user.isAnnouncementTextEnabled) return 2.1;
  if (user.isWhatsNewTextEnabled) return 2.2;

  return 2.3;
};

export const getNotificationTrackingProperties = (notification) => {
  return {
    id: notification.id,
    campaign: notification.campaign,
    campaign_description: notification.campaign_description,
    version: notification.sub_campaign || notification.version,
    version_description: notification.sub_campaign_description || notification.version_description,
    target_product_feature: notification.target_product_feature,
    target_metric: notification.target_metric,
  };
};

export const getNotificationsReadData = (merchant_id) => {
  const lastReadTS = getItem(`announcements-slider-${merchant_id}`) || 0;
  let notifications = [];
  const user = store.getState().session.user;
  const { announcements } = store.getState().growthService.announcements;

  if (user.isGrowthServiceEnabled) {
    notifications.push(...announcements);
  } else {
    if (window.old_notifications) notifications.push(...window.old_notifications);
    if (window.new_notifications) notifications.push(...window.new_notifications);
    notifications.sort((first, second) => second.start_ts - first.start_ts);
  }

  const ID = [];
  const readID = [];
  const unreadID = [];

  let totalUnread = 0;
  for (let i = 0; i < notifications.length; i++) {
    const notifStartTS = notifications[i].start_ts;
    const notifEndTS = notifications[i].end_ts;
    const notifID = notifications[i].id;

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
