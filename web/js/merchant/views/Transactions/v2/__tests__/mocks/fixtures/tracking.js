import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';

jest.mock('common/utils/rzp-utils', () => ({
  getCommonAnalyticsProperties: jest.fn(() => ({ commonProp: 'value' })),
}));

jest.mock('query-string', () => ({
  parse: jest.fn().mockReturnValue({ init_page: 'some-section' }),
}));

export const mockPathname = TransactionsEntityRoute.PAYMENTS;

export const createTrackObject = ({
  objectName,
  actionName = 'Clicked',
  screen = 'Transactions',
  properties,
}) => {
  return {
    objectName,
    actionName,
    screen,
    properties: {
      version: 'v2',
      page: 'Transactions',
      commonProp: 'value',
      ...properties,
    },
  };
};
