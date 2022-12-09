import {
  calculateCreditDebitAmount,
  sanitizeTabName,
  removeUnreconciledEntity,
  fetchBankSettleStatus,
  customSettlementEnabled,
} from 'merchant/views/Settlements/v2/util';
import { fetchBankSettlementStatusHandler } from 'merchant/views/Settlements/v2/components/__test__/mocks/handlers';
import { server } from 'test-utils';

const items = [
  {
    component: 'payment_domestic',
    amount: 10989100,
    count: 21,
    type: 'credit',
    fee: 90534,
    tax: 16294,
    settled_amount: 10882272,
  },
  {
    resourceIdField: 'id',
    component: 'adjustment',
    amount: 100000,
    count: 1,
    type: 'credit',
    fee: 0,
    tax: 0,
    resourceUrl: 'settlements',
    amountInINR: '1000.00',
    settled_amount: 100000,
  },
  {
    resourceIdField: 'id',
    component: 'refund',
    amount: 1000,
    count: 1,
    type: 'debit',
    fee: 0,
    tax: 0,
    resourceUrl: 'settlements',
    amountInINR: '10.00',
    settled_amount: -1000,
  },
];

jest.mock('merchant/models/User', () => ({
  __esModule: true,
  isOrgFeatureExist: jest.fn().mockReturnValueOnce(false).mockReturnValue(true),
}));

describe('Settlement v2 utils', () => {
  describe('calculateCreditDebitAmount', () => {
    test('should calculate credit and debit amount using isBreakUpNew logic when its true', () => {
      const { credit, debit } = calculateCreditDebitAmount(items, true);
      expect(credit).toBe(10982272);
      expect(debit).toBe(-1000);
    });

    test('should not calculate credit and debit amount using isBreakUpNew logic when its false', () => {
      const { credit, debit } = calculateCreditDebitAmount(items, false);
      expect(credit).toBe(11089100);
      expect(debit).toBe(1000);
    });
  });

  describe('sanitizedTabName', () => {
    test('should return tab name without underscore', () => {
      const sanitizedTabName = sanitizeTabName(' Settlement_Test ');
      expect(sanitizedTabName).toBe('Settlement');
    });
  });

  describe('removeUnreconciledEntity', () => {
    test('should remove unreconciled entity from the list', () => {
      const list = [
        {
          id: '1',
          component: 'unreconciled',
        },
        {
          id: '2',
          component: 'reconciled',
        },
      ];
      const filteredList = removeUnreconciledEntity(list);
      expect(filteredList).toEqual([list[1]]);
    });
  });

  describe('fetchBankSettleStatus', () => {
    test('should return bank settlement status', async () => {
      const response = { success: true, data: { status: 'settled' } };
      server.use(fetchBankSettlementStatusHandler(response));
      const status = await fetchBankSettleStatus();
      expect(status).toEqual(response);
    });
  });

  describe('customSettlementEnabled', () => {
    test('should return false when isOrgFeatureExist returns false and cancel_settle_to_bank, old_custom_settl_flow are enabled for the user', () => {
      const isFeatureEnabled = jest.fn().mockReturnValue(true);
      expect(customSettlementEnabled({ isFeatureEnabled })).toBe(false);
    });

    const isFeatureEnabled = jest.fn().mockReturnValue(false);

    test('should return false when isOrgFeatureExist returns true and cancel_settle_to_bank, old_custom_settl_flow are disabled for the user', () => {
      expect(customSettlementEnabled({ isFeatureEnabled })).toBe(true);
    });

    test('should return false when isOrgFeatureExist returns true and one of cancel_settle_to_bank, old_custom_settl_flow are disabled for the user', () => {
      const isFeatureEnabled = jest.fn().mockReturnValueOnce(true).mockReturnValueOnce(false);
      expect(customSettlementEnabled({ isFeatureEnabled })).toBe(false);
    });
  });
});
