import {
  routeToChannelIDMap,
  assetNames,
  growthAssetSchema,
  eventToGrowthEventTypeMap,
} from './data';

const getRouteMap = (isOrgRZP = true) => {
  if (isOrgRZP) return routeToChannelIDMap.rzp;
  else return routeToChannelIDMap.banking;
};

const getGrowthEventTypeFromEvent = (eventName = '') => {
  return eventToGrowthEventTypeMap?.[eventName];
};

export const getChannelID = (fromWhere = 'home', isOrgRZP = true) => {
  const routeMap = getRouteMap(isOrgRZP);
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

const sortBanners = (banners = []) => {
  banners.sort((first, second) => second.override_priority - first.override_priority);
};

export const sortCarouselBanner = (carouselBanner = []) => {
  return carouselBanner.sort((first, second) => {
    return second.sort_key - first.sort_key;
  });
};

export const sortAssetData = (data = [], type = '') => {
  switch (type) {
    case assetNames.ANNOUNCEMENT:
      sortAnnouncements(data);
      break;
    case assetNames.BANNER:
      sortBanners(data);
      break;
    default:
      break;
  }
};

export const isValidAssetData = (data = {}, type = '') => {
  try {
    return growthAssetSchema[type].isValidSync(data);
  } catch (_) {
    return false;
  }
};

export const getAssetTrackingProperties = (
  id = '',
  tracking_data = {},
  oldTrackingData = {},
  event_name = '',
) => {
  const {
    campaign,
    campaign_description,
    sub_campaign,
    sub_campaign_description,
    campaign_id,
    sub_campaign_id,
    tags = {},
  } = tracking_data;
  const { version, version_description, target_metric, target_product_feature } = oldTrackingData;

  const growth_event_type = getGrowthEventTypeFromEvent(event_name);
  return {
    id,
    campaign,
    campaign_description,
    version: sub_campaign || version,
    version_description: sub_campaign_description || version_description,
    campaign_id,
    sub_campaign_id,
    target_metric,
    product_feature: target_product_feature,
    growth_event_type,
    ...tags,
  };
};
