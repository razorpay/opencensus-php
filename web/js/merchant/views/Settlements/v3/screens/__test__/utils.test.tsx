import { validateSettlementIdFilters } from 'merchant/views/Settlements/v3/utils/common';
import { SettlementInfo } from 'common/typings';

const settlementInfo = [
  {
    id: 'setl_KH8Bem0DvFa8FF',
    entity: 'settlement',
    amount: 123331,
    status: 'processed',
    fees: 0,
    tax: 0,
    utr: 'cd1tpsh7e2qa44s89mdg',
    created_at: 1663016751,
  },
] as unknown as SettlementInfo[];

const getFilters = (props = {}) => ({
  status: undefined,
  from: undefined,
  to: undefined,
  utr: undefined,
  ...props,
});

describe('Settlement v3', () => {
  describe('validateSettlementIdFilters', () => {
    test.each([
      [settlementInfo, getFilters()],
      [settlementInfo, getFilters({ status: 'processed' })],
      [settlementInfo, getFilters({ from: '1661970600', to: '1681410599' })],
      [settlementInfo, getFilters({ utr: 'cd1tpsh7e2qa44s89mdg' })],
      [[], getFilters({ from: '1663011751', to: '1663013751' })],
      [[], getFilters({ from: '1663018751', to: '1664016752' })],
      [[], getFilters({ status: 'created' })],
      [[], getFilters({ utr: 'test-utr' })],
    ])('should return %s when filters %s', (output, filters) => {
      expect(validateSettlementIdFilters(settlementInfo, filters)).toStrictEqual(output);
    });
  });
});
