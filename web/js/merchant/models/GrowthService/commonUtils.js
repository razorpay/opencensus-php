import { routeToChannelIDMap, assetNames, announcementSchema } from './data';

export const getChannelID = (fromWhere = 'home') => {
  const channelID =
    routeToChannelIDMap[fromWhere]?.[window.APP_ENV] ||
    routeToChannelIDMap.default[window.APP_ENV] ||
    routeToChannelIDMap.default.production;

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
