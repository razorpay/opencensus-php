import { TransactionsEntityRoute } from 'apps/self-serve/src/App/Transactions/v2/common/constants';

const { FAILED_PAYMENTS, SUCCESS_RATE, DISPUTES } = TransactionsEntityRoute;

export const getHeading = (pathname: TransactionsEntityRoute): string => {
  switch (pathname) {
    case FAILED_PAYMENTS:
      return 'Failed payments';
    case SUCCESS_RATE:
      return 'Success rate';
    case DISPUTES:
      return 'Disputes';
    default:
      return 'Unknown';
  }
};
