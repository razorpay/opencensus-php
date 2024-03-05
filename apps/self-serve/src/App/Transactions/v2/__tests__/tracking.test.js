// eslint-disable-next-line import/order
import { createTrackObject, mockPathname as pathname } from './mocks/fixtures/tracking';
import qs from 'query-string';

import { analyticsTrack } from '@dashboard/shared-utils/analytics';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import {
  track,
  trackTransactionsTabClick,
  trackOverviewDuration,
  trackDurationFilter,
  trackStatusFilter,
  trackMethodFilter,
  trackSearchByFilter,
  trackSearchButton,
  trackDetailsPageLoad,
  trackDetailsCopy,
  trackDetailsClick,
} from 'apps/self-serve/src/App/Transactions/v2/common/tracking';

describe('Tracking Functions', () => {
  test('should call analyticsTrack with correct parameters for track function', () => {
    track({
      objectName: 'Test Object',
      properties: { customProp: 'value' },
    });
    expect(analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Test Object',
      actionName: 'Clicked',
      screen: 'Transactions',
      properties: {
        version: 'v2',
        page: 'Transactions',
        commonProp: 'value',
        customProp: 'value',
      },
    });
  });

  test('should call analyticsTrack with correct parameters for trackTransactionsTabClick function', () => {
    const mockPathname = TransactionsEntityRoute.PAYMENTS;
    trackTransactionsTabClick(mockPathname)();
    expect(analyticsTrack).toHaveBeenCalledWith(
      createTrackObject({
        objectName: 'Transactions Tab',
        properties: {
          tabName: TransactionsPagesMap[mockPathname],
        },
      }),
    );
  });

  test('should call analyticsTrack with correct parameters for trackOverviewDuration function', () => {
    trackOverviewDuration('Last 30 Days');
    expect(analyticsTrack).toHaveBeenCalledWith(
      createTrackObject({
        objectName: 'Overview Date',
        properties: {
          overviewDate: 'Last 30 Days',
          section: 'Overview',
        },
      }),
    );
  });

  test('should call analyticsTrack with correct parameters for trackDurationFilter function', () => {
    trackDurationFilter({
      dateRange: 'Last 7 Days',
      pathname,
    });
    expect(analyticsTrack).toHaveBeenCalledWith(
      createTrackObject({
        objectName: 'Date Dropdown',
        properties: {
          dateRange: 'Last 7 Days',
          section: TransactionsPagesMap[pathname],
        },
      }),
    );
  });

  test('should track status filter', () => {
    trackStatusFilter({
      status: 'Success',
      pathname,
    });
    expect(analyticsTrack).toHaveBeenCalledWith(
      createTrackObject({
        objectName: 'Status Dropdown',
        properties: {
          status: 'Success',
          section: TransactionsPagesMap[pathname],
        },
      }),
    );
  });

  test('should track method filter', () => {
    trackMethodFilter({
      paymentMethodSelected: 'Card',
      pathname,
    });
    expect(analyticsTrack).toHaveBeenCalledWith(
      createTrackObject({
        objectName: 'Method Dropdown',
        properties: {
          paymentMethodSelected: 'Card',
          section: TransactionsPagesMap[pathname],
        },
      }),
    );
  });

  test('should track search by filter', () => {
    trackSearchByFilter({
      searchBy: 'Transaction ID',
      pathname,
    });
    expect(analyticsTrack).toHaveBeenCalledWith(
      createTrackObject({
        objectName: 'Search By Dropdown',
        properties: {
          searchBy: 'Transaction ID',
          section: TransactionsPagesMap[pathname],
        },
      }),
    );
  });

  test('should track search button', () => {
    trackSearchButton({
      searchBy: 'Transaction ID',
      searchByValue: '8955611709',
      countryCode: '+91',
      pathname,
    });
    expect(analyticsTrack).toHaveBeenCalledWith(
      createTrackObject({
        objectName: 'Transaction Information Search',
        properties: {
          searchBy: 'Transaction ID',
          searchByValue: '8955611709',
          countryCode: '+91',
          section: TransactionsPagesMap[pathname],
        },
      }),
    );
  });

  test('should track details page load correctly', () => {
    const trackData = {
      latestTransactionStatus: 'Success',
    };
    trackDetailsPageLoad(trackData);
    expect(analyticsTrack).toHaveBeenCalledWith(
      createTrackObject({
        objectName: 'Transactions Details Page',
        actionName: 'Loaded',
        properties: {
          section: 'some-section',
          latestTransactionStatus: 'Success',
        },
      }),
    );
  });

  test('should track details copy correctly', () => {
    const trackData = {
      objectName: 'Payment ID',
      properties: {
        custom: 'property',
      },
    };
    trackDetailsCopy(trackData);
    expect(qs.parse).toHaveBeenCalledWith(location.search);
    expect(analyticsTrack).toHaveBeenCalledWith(
      createTrackObject({
        objectName: 'Payment ID Copy',
        properties: {
          section: 'some-section',
          custom: 'property',
        },
      }),
    );
  });

  test('should track details click correctly', () => {
    const trackData = {
      objectName: 'Issue Refund',
      properties: {
        custom: 'property',
      },
    };
    trackDetailsClick(trackData);
    expect(qs.parse).toHaveBeenCalledWith(location.search);
    expect(analyticsTrack).toHaveBeenCalledWith(
      createTrackObject({
        objectName: 'Issue Refund',
        properties: {
          section: 'some-section',
          custom: 'property',
        },
      }),
    );
  });
});
