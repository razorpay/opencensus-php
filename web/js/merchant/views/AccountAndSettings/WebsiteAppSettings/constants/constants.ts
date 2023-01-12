import { NewRoutesWebsiteAppSettingsInterface } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/typings';
import {
  ROUTES_INFO,
  OldAndNewRouteMapInterface,
} from 'merchant/views/AccountAndSettings/typings/routes';

export const newRoutes: NewRoutesWebsiteAppSettingsInterface = [
  ROUTES_INFO.WEBSITE_APP_SETTINGS,
  ROUTES_INFO.WEBHOOKS,
  ROUTES_INFO.API_KEYS,
];

export const newAndOldRouteMap: OldAndNewRouteMapInterface = {
  [ROUTES_INFO.API_KEYS]: '/keys',
  [ROUTES_INFO.WEBHOOKS]: '/webhooks',
  [ROUTES_INFO.WEBSITE_APP_SETTINGS]: '/website-app-details',
};
