import moment from 'moment';
import { getItem } from 'common/utils/localStorage';

export const getExperimentVersion = (user) => {
  if (user.isAnnouncementTextEnabled) return 2.1;
  if (user.isWhatsNewTextEnabled) return 2.2;

  return 2.3;
};

export const getNotificationTrackingProperties = (notification) => {
  return {
    id: notification.id,
    version: notification.version,
    campaign: notification.campaign,
    version_description: notification.version_description,
    target_product_feature: notification.target_product_feature,
    target_metric: notification.target_metric,
  };
};

export const getNotificationsReadData = (user) => {
  const lastReadTS = getItem(`announcements-slider-${user}`) || 0;
  const notifications = (window.notifications || []).sort(
    (first, second) => second.start_ts - first.start_ts,
  );
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
