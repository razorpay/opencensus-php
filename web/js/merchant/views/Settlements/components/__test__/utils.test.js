import store from 'merchant/store';
import {
  getSettlementTimeFormat,
  isSettlementSOHBlockEnabled,
  isAccountCodeEnabled,
  isAdditionalUtrEnabled,
} from 'merchant/views/Settlements/components/utils';
describe('Settlement Helper', () => {
  const stateSpy = jest.spyOn(store, 'getState');
  describe('isSettlementSOHBlockEnabled', () => {
    test('should return false if variant is not "on"', () => {
      const splitz = {
        abExperiments: { settlements_soh_block: { variables: { result: 'off' } } },
      };

      const result = isSettlementSOHBlockEnabled(splitz);
      expect(result).toBe(false);
    });
    test('should return true if variant is "on"', () => {
      const splitz = {
        abExperiments: { settlements_soh_block: { variables: { result: 'on' } } },
      };

      const result = isSettlementSOHBlockEnabled(splitz);
      expect(result).toBe(true);
    });
  });

  describe('isAccountCodeEnabled', () => {
    test('should return false if variant is not "on"', () => {
      const splitz = {
        abExperiments: { account_code: { variables: { result: 'off' } } },
      };

      const result = isAccountCodeEnabled(splitz);
      expect(result).toBe(false);
    });
    test('should return true if variant is "on"', () => {
      const splitz = {
        abExperiments: { account_code: { variables: { result: 'on' } } },
      };

      const result = isAccountCodeEnabled(splitz);
      expect(result).toBe(true);
    });
  });

  describe('isAdditionalUtrEnabled', () => {
    test('should return false if variant is not "on"', () => {
      const splitz = {
        abExperiments: { new_22_digit_utr: { variables: { result: 'off' } } },
      };

      const result = isAdditionalUtrEnabled(splitz);
      expect(result).toBe(false);
    });
    test('should return true if variant is "on"', () => {
      const splitz = {
        abExperiments: { new_22_digit_utr: { variables: { result: 'on' } } },
      };

      const result = isAdditionalUtrEnabled(splitz);
      expect(result).toBe(true);
    });
  });

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
