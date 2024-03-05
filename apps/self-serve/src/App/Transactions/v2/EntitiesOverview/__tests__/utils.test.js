import { TransactionsEntityRoute } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { getHeading } from 'apps/self-serve/src/App/Transactions/v2/EntitiesOverview/utils';

describe('utils', () => {
  describe('getHeading', () => {
    test('should return "Failed payments" when the pathname is FAILED_PAYMENTS', () => {
      const result = getHeading(TransactionsEntityRoute.FAILED_PAYMENTS);
      expect(result).toBe('Failed payments');
    });

    test('should return "Success rate" when the pathname is SUCCESS_RATE', () => {
      const result = getHeading(TransactionsEntityRoute.SUCCESS_RATE);
      expect(result).toBe('Success rate');
    });

    test('should return "Disputes" when the pathname is DISPUTES', () => {
      const result = getHeading(TransactionsEntityRoute.DISPUTES);
      expect(result).toBe('Disputes');
    });

    test('should return "Unknown" when the pathname is unknown', () => {
      const result = getHeading('INVALID_ROUTE');
      expect(result).toBe('Unknown');
    });
  });
});
