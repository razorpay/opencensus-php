import moment from 'moment';

import { isADayAgo, isLenderLiquiloans } from 'merchant/views/Capital/CashAdvance/utils';

describe('Capital/CashAdvance/utils', () => {
  describe('isADayAgo', () => {
    it('should return true if a day ago', () => {
      const dayInThePast = moment().subtract(2, 'd');
      expect(isADayAgo(dayInThePast)).toBe(true);
    });

    it('should return false if not a day ago', () => {
      const today = moment();
      expect(isADayAgo(today)).toBe(false);
    });

    it('should return false if no date is passed', () => {
      expect(isADayAgo()).toBe(false);
    });
  });

  describe('isLenderLiquiloans', () => {
    it('should return true if partner_id is LIQUILOANS', () => {
      const withdrawalConfiguration = {
        data: {
          configuration: {
            custom_partner_fields: {
              partner_id: 'LIQUILOANS',
            },
          },
        },
      };

      const result = isLenderLiquiloans(withdrawalConfiguration);

      expect(result).toBe(true);
    });

    it('should return false if partner_id is not LIQUILOANS', () => {
      const withdrawalConfiguration = {
        data: {
          configuration: {
            custom_partner_fields: {
              partner_id: 'GROMOR',
            },
          },
        },
      };

      const result = isLenderLiquiloans(withdrawalConfiguration);

      expect(result).toBe(false);
    });

    it('should return false if withdrawalConfiguration is null', () => {
      const withdrawalConfiguration = null;

      const result = isLenderLiquiloans(withdrawalConfiguration);

      expect(result).toBe(false);
    });

    it('should return false if withdrawalConfiguration is missing properties', () => {
      const withdrawalConfiguration = {};

      const result = isLenderLiquiloans(withdrawalConfiguration);

      expect(result).toBe(false);
    });

    it('should return false if custom_partner_fields is missing', () => {
      const withdrawalConfiguration = {
        data: {
          configuration: {},
        },
      };

      const result = isLenderLiquiloans(withdrawalConfiguration);

      expect(result).toBe(false);
    });
  });
});
