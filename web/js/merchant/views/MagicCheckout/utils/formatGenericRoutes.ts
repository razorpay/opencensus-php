import { PlatformSpecificRoutes, RouteItem } from 'merchant/views/MagicCheckout/types';

import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';

/**
 * Transforms Generic Routes into PLATFORM based Routes format which is used
 * by L2 & L3 common components to render navigation across the application
 */
export const formatRoutesByPlatform = (
  GENERIC_ROUTES: Array<RouteItem>,
): PlatformSpecificRoutes => {
  return Object.values(PLATFORMS).reduce((Routes, Platform) => {
    Routes[Platform] = GENERIC_ROUTES;
    return Routes;
  }, {});
};
