import * as analytics from 'common/utils/analytics';
import {
  trackFundsPageLoadSuccess,
  trackBrandFilterClicked,
  trackBrandFilterCleared,
} from 'merchant/views/GCMS/Funds/events';

import { brandTransactionsResponse } from './mocks/fixtures';
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
  const referenceId = brandTransactionsResponse.data.items[0].reference_id;
  const durationStartDate = 1706711738;
  const durationEndDate = 1706711738;

  test('should call trackFundsPageLoadSuccess with correct parameters', () => {
    trackFundsPageLoadSuccess();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Fund',
      screen: 'FundsPage',
      actionName: 'Page Load Success',
      properties: {
        ...sectionProperties,
      },
    });
  });
  test('should call trackBrandFilterClicked with correct parameters', () => {
    trackBrandFilterClicked({ referenceId, durationStartDate, durationEndDate });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Fund Filters',
      screen: 'FundsPageBrandAccount',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
        referenceId,
        durationStartDate,
        durationEndDate,
      },
    });
  });
  test('should call trackBrandFilterCleared with correct parameters', () => {
    trackBrandFilterCleared();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Fund Filters',
      screen: 'FundsPageBrandAccount',
      actionName: 'Cleared',
      properties: {
        ...sectionProperties,
      },
    });
  });
});
