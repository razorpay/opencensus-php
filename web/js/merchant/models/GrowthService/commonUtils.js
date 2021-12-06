import {
  routeToChannelIDMap,
  bankingRouteToChannelIDMap,
  assetNames,
  announcementSchema,
} from './data';

export const getChannelID = (fromWhere = 'home', isRzpORG = true) => {
  const routeMap = isRzpORG ? routeToChannelIDMap : bankingRouteToChannelIDMap;
  const channelID =
    routeMap[fromWhere]?.[window.APP_ENV] ||
    routeMap.default[window.APP_ENV] ||
    routeMap.default.production;

  return channelID;
};

const sortAnnouncements = (announcements = []) => {
  announcements.sort((first, second) => {
    if (first.id === 'projectNitro') return -1;
    if (first.id === 'whats-new-JUL21-RXCC-ULTRA' && second.id !== 'projectNitro') return -1;
    if (second.id === 'projectNitro') return 1;
    if (second.id === 'whats-new-JUL21-RXCC-ULTRA') return 1;
    return second.start_ts - first.start_ts;
  });
};

export const sortAssetData = (data = [], type = '') => {
  switch (type) {
    case assetNames.ANNOUNCEMENT:
      sortAnnouncements(data);
      break;
    default:
      break;
  }
};

export const isValidAssetData = (data = {}, type = '') => {
  try {
    switch (type) {
      case assetNames.ANNOUNCEMENT:
        return announcementSchema.isValidSync(data);
      default:
        return false;
    }
  } catch (_) {
    return false;
  }
};

export const getAssetTrackingProperties = (id = '', tracking_data = {}, oldTrackingData = {}) => {
  const {
    campaign,
    campaign_description,
    sub_campaign,
    sub_campaign_description,
    campaign_id,
    sub_campaign_id,
    meta,
  } = tracking_data;
  const { version, version_description, target_metric, target_product_feature } = oldTrackingData;
  let product_feature;
  if (meta) product_feature = meta.product_feature;

  return {
    id,
    campaign,
    campaign_description,
    version: sub_campaign || version,
    version_description: sub_campaign_description || version_description,
    campaign_id,
    sub_campaign_id,
    target_metric,
    product_feature: product_feature || target_product_feature,
  };
};
