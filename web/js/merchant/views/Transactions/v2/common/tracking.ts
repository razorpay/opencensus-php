import qs from 'query-string';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { TransactionsEntityRoute, TransactionsPagesMap } from './constants';
import { Track, TrackSearchButton } from './types';

export const track = ({
  objectName,
  actionName = 'Clicked',
  screen = 'Transactions',
  properties,
}: Track): void => {
  analyticsTrack({
    objectName,
    actionName,
    screen,
    properties: {
      version: 'v2',
      page: 'Transactions',
      ...properties,
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
    },
  });
};

export const trackTransactionsTabClick = (pathname: TransactionsEntityRoute) => (): void =>
  track({
    objectName: 'Transactions Tab',
    properties: {
      tabName: TransactionsPagesMap[pathname],
    },
  });

export const trackOverviewDuration = (title: string): void => {
  track({
    objectName: 'Overview Date',
    properties: { overviewDate: title, section: 'Overview' },
  });
};

export const trackDurationFilter = ({
  dateRange,
  pathname,
}: {
  dateRange: string;
  pathname: string;
}): void => {
  track({
    objectName: 'Date Dropdown',
    properties: { dateRange, section: TransactionsPagesMap[pathname] },
  });
};

export const trackStatusFilter = ({
  status,
  pathname,
}: {
  status: string;
  pathname: string;
}): void => {
  track({
    objectName: 'Status Dropdown',
    properties: { status, section: TransactionsPagesMap[pathname] },
  });
};

export const trackMethodFilter = ({
  paymentMethodSelected,
  pathname,
}: {
  paymentMethodSelected: string;
  pathname: string;
}): void => {
  track({
    objectName: 'Method Dropdown',
    properties: {
      paymentMethodSelected,
      section: TransactionsPagesMap[pathname],
    },
  });
};

export const trackSearchByFilter = ({
  searchBy,
  pathname,
}: {
  searchBy: string;
  pathname: string;
}): void => {
  track({
    objectName: 'Search By Dropdown',
    properties: { searchBy, section: TransactionsPagesMap[pathname] },
  });
};

export const trackSearchButton = ({
  searchBy,
  searchByValue,
  countryCode,
  pathname,
}: TrackSearchButton): void => {
  track({
    objectName: 'Transaction Information Search',
    properties: {
      searchBy,
      searchByValue,
      countryCode,
      section: TransactionsPagesMap[pathname],
    },
  });
};

export const trackDetailsPageLoad = ({
  latestTransactionStatus,
}: {
  latestTransactionStatus: string;
}): void => {
  const { init_page: section } = qs.parse(location.search);
  track({
    objectName: 'Transactions Details Page',
    actionName: 'Loaded',
    properties: {
      section,
      latestTransactionStatus,
    },
  });
};

export const trackDetailsCopy = ({
  objectName,
  properties,
}: {
  objectName: string;
  properties?: Record<string, string>;
}): void => {
  const { init_page: section } = qs.parse(location.search);
  track({
    objectName: `${objectName} Copy`,
    properties: {
      section,
      ...properties,
    },
  });
};

export const trackDetailsClick = ({
  objectName,
  properties,
}: {
  objectName: string;
  properties?: Record<string, string | undefined>;
}): void => {
  const { init_page: section } = qs.parse(location.search);
  track({
    objectName,
    properties: {
      section,
      ...properties,
    },
  });
};
