import { TransactionsEntityRoute } from 'apps/self-serve/src/App/Transactions/v2/common/constants';

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
