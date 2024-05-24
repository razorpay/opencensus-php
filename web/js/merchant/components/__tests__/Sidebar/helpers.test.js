import {
  getIsShowAffordabilityWidget,
  getIsCheckoutPaymentMetricsEnabled,
} from 'merchant/components/Sidebar/helpers';

describe('Helpers', () => {
  describe('getIsShowAffordabilityWidget', () => {
    it('should return true if currentUser has all required properties as true', () => {
      const currentUser = {
        isShowAffordabilityWidget: true,
        isOrgRZP: true,
        isINCountry: true,
      };
      expect(getIsShowAffordabilityWidget(currentUser)).toBe(true);
    });

    it('should return false if any required property is false', () => {
      const currentUser1 = {
        isShowAffordabilityWidget: false,
        isOrgRZP: true,
        isINCountry: true,
      };
      const currentUser2 = {
        isShowAffordabilityWidget: true,
        isOrgRZP: false,
        isINCountry: true,
      };
      const currentUser3 = {
        isShowAffordabilityWidget: true,
        isOrgRZP: true,
        isINCountry: false,
      };
      expect(getIsShowAffordabilityWidget(currentUser1)).toBe(false);
      expect(getIsShowAffordabilityWidget(currentUser2)).toBe(false);
      expect(getIsShowAffordabilityWidget(currentUser3)).toBe(false);
    });
  });

  describe('getIsCheckoutPaymentMetricsEnabled', () => {
    it('should return true if currentUser has all required properties as true', () => {
      const currentUser = {
        isCheckoutAnalyticsEnabled: true,
        isOrgRZP: true,
        isINCountry: true,
      };
      expect(getIsCheckoutPaymentMetricsEnabled(currentUser)).toBe(true);
    });

    it('should return false if any required property is false', () => {
      const currentUser1 = {
        isCheckoutAnalyticsEnabled: false,
        isOrgRZP: true,
        isINCountry: true,
      };
      const currentUser2 = {
        isCheckoutAnalyticsEnabled: true,
        isOrgRZP: false,
        isINCountry: true,
      };
      const currentUser3 = {
        isCheckoutAnalyticsEnabled: true,
        isOrgRZP: true,
        isINCountry: false,
      };
      expect(getIsCheckoutPaymentMetricsEnabled(currentUser1)).toBe(false);
      expect(getIsCheckoutPaymentMetricsEnabled(currentUser2)).toBe(false);
      expect(getIsCheckoutPaymentMetricsEnabled(currentUser3)).toBe(false);
    });
  });
});
