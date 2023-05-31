import {
  routeToChannelIDMap,
  assetNames,
  growthAssetSchema,
  eventToGrowthEventTypeMap,
} from './data';
import { getUser } from 'merchant/store';
import get from 'lodash/get';

const getRouteMap = (isOrgRZP = true) => {
  if (isOrgRZP) return routeToChannelIDMap.rzp;
  else return routeToChannelIDMap.banking;
};

const getGrowthEventTypeFromEvent = (eventName = '') => {
  return eventToGrowthEventTypeMap?.[eventName];
};

export const getChannelID = (fromWhere = 'home', isOrgRZP = true) => {
  const routeMap = getRouteMap(isOrgRZP);
  const channelID = routeMap[fromWhere]?.[window.APP_ENV];

  return channelID || 'xxxxxxxxxxxxxx';
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
    template_id,
    channel_id,
    tags = {},
  } = tracking_data;
  const { version, version_description, target_metric, target_product_feature } = oldTrackingData;

  const growth_event_type = getGrowthEventTypeFromEvent(event_name);
  return {
    trackingID: id,
    campaign,
    campaign_description,
    version: sub_campaign || version,
    version_description: sub_campaign_description || version_description,
    campaign_id,
    sub_campaign_id,
    target_metric,
    product_feature: target_product_feature,
    growth_event_type,
    template_id,
    channel_id,
    ...tags,
  };
};

/**
 * takes a url as a string & replace "${*}", in the url
 * with user information, to open custom url based on login user
 * Eg. www.razorpay.com/mid=${rzp_user.user.id} -> www.razorpay.com/mid=G5KWPzRBj0XXXX
 * @param {string} stringToInterpolate - url to be opened
 * @returns {*} - returns modified url
 */
export const stringToLiteral = (stringToInterpolate = '') => {
  const user = getUser();

  const database = {
    user,
    rzp_user: user,
  };

  return stringToInterpolate.replace(/\$\{.+?}/g, (match) => {
    const path = match.substring(2, match.length - 1).trim();

    const value = get(database, path);

    return value === undefined ? match : value;
  });
};
