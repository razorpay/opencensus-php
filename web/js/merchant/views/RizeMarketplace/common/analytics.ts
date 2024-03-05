import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { EventActionsType, FetchProductsResponse } from './types';

const MARKETPLACE = 'Rize - Marketplace';
const MARKETPLACE_SCREEN = 'Marketplace';

const MarketplaceEventActions: EventActionsType = {
  CAROUSEL_LOADED_SUCCESS: {
    objectName: 'Carousel Loaded',
    actionName: 'Success',
    screen: MARKETPLACE_SCREEN,
  },
  VISIT_MARKETPLACE_INITIATED: {
    objectName: 'Visit Marketplace',
    actionName: 'Initiated',
    screen: MARKETPLACE_SCREEN,
  },
  LOADED_SUCCESS: {
    objectName: 'Loaded',
    actionName: 'Success',
    screen: MARKETPLACE_SCREEN,
  },
  FILTER_SEARCH_INITIATED: {
    objectName: 'Filter Search',
    actionName: 'Initiated',
    screen: MARKETPLACE_SCREEN,
  },
  COMBINATION_FILTER_CLICKED: {
    objectName: 'Combination Filter',
    actionName: 'Clicked',
    screen: MARKETPLACE_SCREEN,
  },
  DEAL_TILE_CLICKED: {
    objectName: 'Deal Tile',
    actionName: 'Clicked',
    screen: MARKETPLACE_SCREEN,
  },
  KNOW_MORE_COMMUNITY_INITIATED: {
    objectName: 'Know More Community',
    actionName: 'Initiated',
    screen: MARKETPLACE_SCREEN,
  },
};

const MarketplaceProductEventActions: EventActionsType = {
  LOADED_SUCCESS: {
    objectName: 'Loaded',
    actionName: 'Success',
    screen: 'Product Page',
  },
};

const MarketplaceDealCardEventActions: EventActionsType = {
  AVAIL_THIS_DEAL_CTA_CLICKED: {
    objectName: 'Avail This Deal CTA',
    actionName: 'Clicked',
    screen: 'Not Availed Card',
  },
  COPY_COUPON_CODE_CLICKED: {
    objectName: 'Copy Coupon Code',
    actionName: 'Clicked',
    screen: 'Availed Card',
  },
  APPLY_HERE_CTA_CLICKED: {
    objectName: 'Apply Here CTA',
    actionName: 'Clicked',
    screen: 'Availed Card',
  },
  VISIT_WEBSITE_CLICKED: {
    objectName: 'Visit Website',
    actionName: 'Clicked',
    screen: 'Availed Card',
  },
};

const track: typeof analyticsTrack = ({ properties, ...rest }) => {
  return analyticsTrack({
    ...rest,
    properties: {
      category: MARKETPLACE,
      url: window.location.href,
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...properties,
    },
  });
};

export const trackCarouselLoadedSuccess = (): void => {
  track(MarketplaceEventActions.CAROUSEL_LOADED_SUCCESS);
};

export const trackVisitMarketplaceInitiated = (): void => {
  track(MarketplaceEventActions.VISIT_MARKETPLACE_INITIATED);
};

export const trackMarketplaceLoadedSuccess = (): void => {
  track(MarketplaceEventActions.LOADED_SUCCESS);
};

export const trackKnowMoreCommunityInitiated = (
  initiated_from: 'homepage' | 'productpage',
): void => {
  track({
    ...MarketplaceEventActions.KNOW_MORE_COMMUNITY_INITIATED,
    properties: {
      initiated_from,
    },
  });
};

export const trackFilterSearchInitiated = (search_term: string): void => {
  track({
    ...MarketplaceEventActions.FILTER_SEARCH_INITIATED,
    properties: {
      search_term,
    },
  });
};

export const trackCombinationFilterClicked = (filter_type: string[]): void => {
  track({
    ...MarketplaceEventActions.COMBINATION_FILTER_CLICKED,
    properties: {
      filter_type,
    },
  });
};

type ProductInfo = Pick<FetchProductsResponse['results'][0], 'id' | 'name' | 'category' | 'type'>;

export const trackDealTileClicked = (
  product: ProductInfo,
  section: 'Whats_new' | 'Products' | 'More_in_Category' | 'app_store_carousel',
): void => {
  track({
    ...MarketplaceEventActions.DEAL_TILE_CLICKED,
    properties: {
      product,
      section,
    },
  });
};

export const trackProductLoadedSuccess = (product: ProductInfo): void => {
  track({
    ...MarketplaceProductEventActions.LOADED_SUCCESS,
    properties: {
      product,
    },
  });
};

export const trackAvailThisDealCtaClicked = (product: ProductInfo): void => {
  track({
    ...MarketplaceDealCardEventActions.AVAIL_THIS_DEAL_CTA_CLICKED,
    properties: {
      product,
    },
  });
};

export const trackCopyCouponCodeClicked = (product: ProductInfo): void => {
  track({
    ...MarketplaceDealCardEventActions.COPY_COUPON_CODE_CLICKED,
    properties: {
      product,
    },
  });
};

export const trackApplyHereCtaClicked = (product: ProductInfo): void => {
  track({
    ...MarketplaceDealCardEventActions.APPLY_HERE_CTA_CLICKED,
    properties: {
      product,
    },
  });
};

export const trackVisitWebsiteClicked = (product: ProductInfo): void => {
  track({
    ...MarketplaceDealCardEventActions.VISIT_WEBSITE_CLICKED,
    properties: {
      product,
    },
  });
};
