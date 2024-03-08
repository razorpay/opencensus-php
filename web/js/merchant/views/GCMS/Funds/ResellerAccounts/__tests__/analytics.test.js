import * as analytics from 'common/utils/analytics';
import {
  trackResellerFilterClicked,
  trackResellerFilterCleared,
} from 'merchant/views/GCMS/Funds/events';

import { gcmsFundsResellerAccountsResponse } from './mocks/fixtures';

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

  const resellerName = gcmsFundsResellerAccountsResponse.data.items[0].merchant_name;
  test('should call trackResellerFilterClicked with correct parameters', () => {
    trackResellerFilterClicked({ resellerName });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Fund Filters',
      screen: 'FundsPageResellerAccount',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
        resellerName,
      },
    });
  });
  test('should call trackResellerFilterCleared with correct parameters', () => {
    trackResellerFilterCleared();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Fund Filters',
      screen: 'FundsPageResellerAccount',
      actionName: 'Cleared',
      properties: {
        ...sectionProperties,
      },
    });
  });
});
