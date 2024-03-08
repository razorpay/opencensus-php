import { track } from 'merchant/views/GCMS/shared/analytics';

const OBJECT_NAMES = {
  RESELLER: 'Reseller',
  RESELLERS_DETAILS: 'Resellers Details',
  RESELLERS_FILTER: 'Resellers Filters',
};

export const trackResellersPageLoadSuccess = () => {
  track({
    objectName: OBJECT_NAMES.RESELLER,
    screen: 'ResellersPage',
    actionName: 'Page Load Success',
  });
};

export const trackResellerDetailsPageClicked = ({ resellerId, resellerName }) => {
  track({
    objectName: OBJECT_NAMES.RESELLERS_DETAILS,
    screen: 'ReselllersDetailsPage',
    actionName: 'Clicked',
    properties: {
      resellerId,
      resellerName,
    },
  });
};

export const trackResellersDetailsPageLoadSuccess = ({ resellerId, orderId }) => {
  track({
    objectName: OBJECT_NAMES.RESELLERS_DETAILS,
    screen: 'ReselllersDetailsPage',
    actionName: 'Page Load Success',
    properties: {
      resellerId,
      orderId,
    },
  });
};

export const trackResellersFiltersClicked = ({ resellerName, status }) => {
  track({
    objectName: OBJECT_NAMES.RESELLERS_FILTER,
    screen: 'ResellersFilters',
    actionName: 'Clicked',
    properties: {
      resellerName,
      status,
    },
  });
};

export const trackResellersFiltersCleared = () => {
  track({
    objectName: OBJECT_NAMES.RESELLERS_FILTER,
    screen: 'ResellersFilters',
    actionName: 'Cleared',
  });
};
