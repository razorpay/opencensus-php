import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';

import { PlatformSpecificRoutes } from 'merchant/views/MagicCheckout/types';

export const convertMagicRoutesToConfigurationFlow = (routes) => {
  return (
    routes?.map((route) => {
      const updatedRoute = { ...route };
      updatedRoute.path = updatedRoute.path.replace('/magic/', '/configuration/magic/');
      return updatedRoute;
    }) || []
  );
};

//Provides full page view(FPV route - /configuration/magic/*) route support to platform based routes
export const convertPlatformRoutesToConfigurationFlow = (
  PlatformSpecificRoutes: PlatformSpecificRoutes,
): PlatformSpecificRoutes => {
  return Object.values(PLATFORMS).reduce((fpvRoutes, platform) => {
    fpvRoutes[platform] = convertMagicRoutesToConfigurationFlow(PlatformSpecificRoutes?.[platform]);
    return fpvRoutes;
  }, {});
};

export const checkMagicConfigurationFlow = () => location.pathname.includes('configuration/magic');
