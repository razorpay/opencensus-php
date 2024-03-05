import qs from 'query-string';

import { analyticsTrack } from '@dashboard/shared-utils/analytics';
import {
  getCommonAnalyticsProperties,
  decodeSensitiveFields,
} from '@dashboard/shared-utils/rzp-utils';

import { LAST_7_DAYS, TransactionsEntityRoute, TransactionsPagesMap } from './constants';
import { Track, TrackSearchButton } from './types';
import { endOfDay, getFromTime } from './utils';
import { SearchQueryParamType } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsListFilter/types';

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

export const trackNoSearchResult = ({ section }: { section: string }): void => {
  const search = decodeSensitiveFields(qs.parse(location.search)) as Record<
    SearchQueryParamType,
    string | null
  >;
  const {
    from,
    to,
    status,
    method,
    id,
    email,
    contact,
    country_code: countryCode,
    order_id: orderId,
    payment_id: paymentId,
    public_status,
  } = search;

  track({
    objectName: 'No Transaction Entities',
    actionName: 'Found',
    properties: {
      section,
      from: from || getFromTime(LAST_7_DAYS).unix(),
      to: to || endOfDay.unix(),
      status,
      public_status,
      method,
      id,
      email,
      contact,
      countryCode,
      orderId,
      paymentId,
    },
  });
};
