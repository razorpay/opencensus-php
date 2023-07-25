import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import { SEARCH_PRODUCTS } from 'merchant/components/HeaderNav/UniversalSearch/constants/SearchProducts';
import {
  EligibleProducts,
  EligibleProductsTypes,
  ObjType,
} from 'merchant/components/HeaderNav/UniversalSearch/typings';

export const options = {
  keys: [
    {
      name: 'tags.value',
      weight: 0.4,
    },
    {
      name: 'title',
      weight: 0.6,
    },
  ],
  includeScore: true,
  ignoreLocation: false,
  threshold: 0.4,
  minMatchCharLength: 5,
};

export const multiKeyOptions = {
  keys: [
    {
      name: 'tags.value',
      weight: 0.4,
    },
    {
      name: 'title',
      weight: 0.6,
    },
  ],
  includeScore: true,
  threshold: 0.15,
  minMatchCharLength: 2,
};

export const feature = 'allow_cfb_international';

export const getEligibleProductsForMerchants = (
  {
    user,
    instruments,
    mode,
    websiteSectionDetailsData,
    allowCFBInternational,
    hasEnrolled,
  }: EligibleProductsTypes,
  skipApiConditions: boolean,
): EligibleProducts[] => {
  let productsToFilter = SEARCH_PRODUCTS;
  if (skipApiConditions) {
    productsToFilter = productsToFilter.filter((p) => !p.apiCondition);
  }
  const filteredProducts = productsToFilter.filter(({ additionalCondition }): boolean => {
    return additionalCondition({
      user,
      mode,
      websiteSectionDetailsData,
      hasEnrolled,
      allowCFBInternational,
      instruments,
    });
  });
  return filteredProducts;
};

const addCommonProperties = (): ObjType => {
  return {
    version: 'v1',
    ...getCommonSegmentProperties(window.rzp_user, { addUserProperties: true }),
  };
};

export const trackSearchBarInfo = (props = {}): void => {
  analyticsTrack({
    objectName: 'Search Bar Introduction',
    actionName: 'Clicked',
    screen: 'home page',
    properties: {
      ...addCommonProperties(),
      ...props,
    },
  });
};

export const trackSearchBarClicked = (): void => {
  analyticsTrack({
    objectName: 'Search Bar',
    actionName: 'Clicked',
    screen: 'home page',
    properties: {
      ...addCommonProperties(),
    },
  });
};

export const TRACK_TYPE_DEBOUNCE_DURATION = 1500;

export const trackSearchTypeInitiated = (props = {}): void => {
  analyticsTrack({
    objectName: 'Search Type',
    actionName: 'Initiated',
    screen: 'home page',
    properties: {
      ...addCommonProperties(),
      ...props,
    },
  });
};

export const trackSearchResultClicked = (props = {}): void => {
  analyticsTrack({
    objectName: 'Search Result',
    actionName: 'Clicked',
    screen: 'home page',
    properties: {
      ...addCommonProperties(),
      ...props,
    },
  });
};

export const handleTestModeVisibility = (isFocussed: boolean): void => {
  const testModeBanner = document.querySelector('.highlight-test-mode-container') as HTMLElement;
  if (testModeBanner) {
    if (isFocussed) {
      testModeBanner.style.visibility = 'hidden';
    } else if (testModeBanner?.style?.visibility === 'hidden') {
      testModeBanner.style.visibility = 'visible';
    }
  }
};
