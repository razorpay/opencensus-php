/* eslint-disable @typescript-eslint/prefer-nullish-coalescing */
/* eslint-disable @typescript-eslint/no-unnecessary-condition */
import { type RazorpayUser } from '@libs/shared-types';
import { isMobileDevice } from './isMobileDevice';
import { getMode } from './mode';

interface Config {
  addUserProperties?: boolean;
  isStorefrontPage?: boolean;
}

/**
 * Retrieves common analytics properties based on user and configuration.
 *
 * @param {User | null} user - The user object containing user details.
 * @param {Config} config - The configuration object.
 * @returns {Record<string, any>} - Common analytics properties.
 *
 * @example
 * const user = {
 *   id: '123',
 *   user: { id: '456' },
 *   role: 'admin',
 *   current: 'merchant_123',
 * };
 * const analyticsProps = getCommonAnalyticsProperties(user, { addUserProperties: true });
 * console.log(analyticsProps);
 * // Output: { userId: '456', mode: '...', userRole: 'admin', merchantId: 'merchant_123', ... }
 */
export function getCommonAnalyticsProperties(
  user: RazorpayUser | null,
  config: Config = {},
): Record<string, any> {
  if (!user) {
    // Skip properties if the session is expired or user details are unavailable
    return {};
  }

  const { addUserProperties = false, isStorefrontPage = false } = config;

  let userProperties: Record<string, any> = {};

  if (addUserProperties) {
    userProperties = {
      business_type: user?.business_type,
      activation_status: user?.activated,
      current_activation_status: user?.activation_status,
      previous_activation_status:
        user?.activationStatusChangeLogs?.[user?.activationStatusChangeLogs?.length - 1]?.[
          'activation_status'
        ],
      user_business_category: user?.business_category,
      user_business_sub_category: user?.business_subcategory,
    };
  }

  let storefrontProperties: Record<string, any> = {};

  if (isStorefrontPage) {
    storefrontProperties = {
      // @ts-expect-error - Property 'razorpayAnalytics' does not exist on type 'Window'.
      browser: window?.razorpayAnalytics?.utils?.getBrowserDetails?.(),
      device_type: isMobileDevice?.(1020) ? 'mweb' : 'dweb',
    };
  }

  const mode = getMode(user?.id);

  return {
    userId: user?.user?.id || 'Unknown',
    mode,
    userRole: user?.role || 'Unknown',
    merchantId: user?.current || 'Unknown',
    ...userProperties,
    ...storefrontProperties,
  };
}
