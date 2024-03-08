import * as analytics from 'common/utils/analytics';
import {
  trackResellersPageLoadSuccess,
  trackResellerDetailsPageClicked,
  trackResellersDetailsPageLoadSuccess,
  trackResellersFiltersClicked,
  trackResellersFiltersCleared,
} from 'merchant/views/GCMS/Resellers/events';

import { resellersListResponse } from './mocks/fixtures';

const trackSpy = jest.spyOn(analytics, 'analyticsTrack');

const commonProperties = {
  properties: {},
};

describe('Tests for analytics functions', () => {
  const sectionProperties = {
    location: 'GCMS',
    sessionId: 'not available',
    validity: 'not available',
  };

  const resellerId = resellersListResponse.data.items[0].id;
  const resellerName = resellersListResponse.data.items[0].merchant_name;
  const status = resellersListResponse.data.items[0].status;
  const orderId = 'dummy order Id';
  test('should call trackResellersPageLoadSuccess with correct parameters', () => {
    trackResellersPageLoadSuccess();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Reseller',
      screen: 'ResellersPage',
      actionName: 'Page Load Success',
      properties: {
        ...sectionProperties,
      },
    });
  });
  test('should call trackResellerDetailsPageClicked with correct parameters', () => {
    trackResellerDetailsPageClicked({ resellerId, resellerName });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Resellers Details',
      screen: 'ReselllersDetailsPage',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
        resellerId,
        resellerName,
      },
    });
  });
  test('should call trackResellersDetailsPageLoadSuccess with correct parameters', () => {
    trackResellersDetailsPageLoadSuccess({ resellerId, orderId });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Resellers Details',
      screen: 'ReselllersDetailsPage',
      actionName: 'Page Load Success',
      properties: {
        ...sectionProperties,
        resellerId,
        orderId,
      },
    });
  });
  test('should call trackResellersFiltersClicked with correct parameters', () => {
    trackResellersFiltersClicked({ resellerName, status });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Resellers Filters',
      screen: 'ResellersFilters',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
        resellerName,
        status,
      },
    });
  });
  test('should call trackResellersFiltersCleared with correct parameters', () => {
    trackResellersFiltersCleared();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Resellers Filters',
      screen: 'ResellersFilters',
      actionName: 'Cleared',
      properties: {
        ...sectionProperties,
      },
    });
  });
});
