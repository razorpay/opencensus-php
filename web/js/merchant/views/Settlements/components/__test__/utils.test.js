import store from 'merchant/store';
import { getSettlementTimeFormat } from 'merchant/views/Settlements/components/utils';
describe('Settlement Helper', () => {
  const stateSpy = jest.spyOn(store, 'getState');

  test('should return settlement date with time when the org feature flag "hide_settlement_time" is disabled', () => {
    stateSpy.mockReturnValue({
      session: {
        org: {
          features: [],
        },
      },
    });
    expect(getSettlementTimeFormat('DD MMM YYYY, hh:mm:ss a')).toBe('DD MMM YYYY, hh:mm:ss a');
  });

  test('should return settlement date without time when the org feature flag "hide_settlement_time" is enabled', () => {
    stateSpy.mockReturnValue({
      session: {
        org: {
          features: ['hide_settlement_time'],
        },
      },
    });
    expect(getSettlementTimeFormat('DD MMM YYYY, hh:mm:ss a')).toBe('DD MMM YYYY');
  });

  test('should return settlement date with time(default value) when the org feature flag "hide_settlement_time" is disabled', () => {
    stateSpy.mockReturnValue({
      session: {
        org: {
          features: [],
        },
      },
    });
    expect(getSettlementTimeFormat()).toBe('DD MMM YYYY, hh:mm:ss a');
  });
});
