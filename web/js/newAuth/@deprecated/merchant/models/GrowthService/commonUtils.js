// web/js/merchant/models/GrowthService/commonUtils.js
import { routeToChannelIDMap } from './data';

const getRouteMap = (isOrgRZP = true) => {
  if (isOrgRZP) return routeToChannelIDMap.rzp;
  else return routeToChannelIDMap.banking;
};

export const getChannelID = (fromWhere = 'home', isOrgRZP = true) => {
  const routeMap = getRouteMap(isOrgRZP);
  const channelID = routeMap[fromWhere]?.[window.APP_ENV];

  return channelID || 'xxxxxxxxxxxxxx';
};
