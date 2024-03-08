import { track } from 'merchant/views/GCMS/shared/analytics';

const OBJECT_NAMES = {
  FUND: 'Fund',
  FILTER: 'Fund Filters',
};

export const trackFundsPageLoadSuccess = () => {
  track({
    objectName: OBJECT_NAMES.FUND,
    screen: 'FundsPage',
    actionName: 'Page Load Success',
  });
};

export const trackBrandFilterClicked = ({ referenceId, durationStartDate, durationEndDate }) => {
  track({
    objectName: OBJECT_NAMES.FILTER,
    screen: 'FundsPageBrandAccount',
    actionName: 'Clicked',
    properties: {
      referenceId,
      durationStartDate,
      durationEndDate,
    },
  });
};

export const trackBrandFilterCleared = () => {
  track({
    objectName: OBJECT_NAMES.FILTER,
    screen: 'FundsPageBrandAccount',
    actionName: 'Cleared',
  });
};

export const trackResellerFilterClicked = ({ resellerName }) => {
  track({
    objectName: OBJECT_NAMES.FILTER,
    screen: 'FundsPageResellerAccount',
    actionName: 'Clicked',
    properties: {
      resellerName,
    },
  });
};

export const trackResellerFilterCleared = () => {
  track({
    objectName: OBJECT_NAMES.FILTER,
    screen: 'FundsPageResellerAccount',
    actionName: 'Cleared',
  });
};
