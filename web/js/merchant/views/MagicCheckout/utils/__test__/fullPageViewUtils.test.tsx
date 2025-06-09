import React from 'react';

import {
  convertMagicRoutesToConfigurationFlow,
  convertPlatformRoutesToConfigurationFlow,
} from 'merchant/views/MagicCheckout/utils/Configuration';

import { PlatformSpecificRoutes } from 'merchant/views/MagicCheckout/types';

const TestComponent = () => <div>Test Component</div>;

describe('Full Page View Utils', () => {
  describe('convertMagicRoutesToConfigurationFlow', () => {
    it('should convert magic routes to configuration flow by updating the path', () => {
      const inputRoutes = [
        { path: '/magic/settings', Component: TestComponent },
        { path: '/magic/analytics', Component: TestComponent },
      ];

      const expectedRoutes = [
        { path: '/configuration/magic/settings', Component: TestComponent },
        { path: '/configuration/magic/analytics', Component: TestComponent },
      ];

      const result = convertMagicRoutesToConfigurationFlow(inputRoutes);
      expect(result).toEqual(expectedRoutes);
    });

    it('should return an empty array if routes are undefined', () => {
      const result = convertMagicRoutesToConfigurationFlow(undefined);
      expect(result).toEqual([]);
    });
  });

  describe('convertPlatformRoutesToConfigurationFlow', () => {
    it('should convert platform-specific routes to configuration flow', () => {
      const inputRoutes: PlatformSpecificRoutes = {
        shopify: [{ path: '/magic/settings', Component: TestComponent, label: 'Settings' }],
        woocommerce: [{ path: '/magic/analytics', Component: TestComponent, label: 'Analytics' }],
        native: [{ path: '/magic/Orders', Component: TestComponent, label: 'Orders' }],
        magento: [{ path: '/magic/coupons', Component: TestComponent, label: 'Coupons' }],
      };

      const expectedRoutes: PlatformSpecificRoutes = {
        shopify: [
          { path: '/configuration/magic/settings', Component: TestComponent, label: 'Settings' },
        ],
        woocommerce: [
          { path: '/configuration/magic/analytics', Component: TestComponent, label: 'Analytics' },
        ],
        native: [
          { path: '/configuration/magic/Orders', Component: TestComponent, label: 'Orders' },
        ],
        magento: [
          { path: '/configuration/magic/coupons', Component: TestComponent, label: 'Coupons' },
        ],
      };

      const result = convertPlatformRoutesToConfigurationFlow(inputRoutes);
      expect(result).toEqual(expectedRoutes);
    });

    it('should handle platforms without routes', () => {
      const inputRoutes: PlatformSpecificRoutes = {
        woocommerce: [{ path: '/magic/analytics', Component: TestComponent, label: 'Settings' }],
      };

      const expectedRoutes: PlatformSpecificRoutes = {
        shopify: [],
        woocommerce: [
          { path: '/configuration/magic/analytics', Component: TestComponent, label: 'Settings' },
        ],
        native: [],
        magento: [],
      };

      const result = convertPlatformRoutesToConfigurationFlow(inputRoutes);
      expect(result).toEqual(expectedRoutes);
    });
  });
});
