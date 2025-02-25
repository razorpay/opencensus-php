import { type RazorpayUser } from '@libs/shared-types';
import { getMode } from './mode';

interface Config {
  addUserProperties?: boolean;
}

/**
 * Retrieves common segment properties for analytics.
 *
 * @param {RazorpayUser | null} user - The user object from which properties are extracted.
 * @param {Config} config - Configuration to add additional user properties.
 * @returns {Record<string, any>} - Object containing segment properties like user role, page URL, etc.
 *
 * @example
 * const user = {
 *   id: '123',
 *   user: { id: '456' },
 *   role: 'admin',
 *   current: 'merchant_123',
 *   business_type: 'retail',
 *   activated: true,
 *   activation_status: 'active',
 *   activationStatusChangeLogs: [{ activation_status: 'pending' }]
 * };
 * const segmentProps = getCommonSegmentProperties(user, { addUserProperties: true });
 * console.log(segmentProps);
 * // Output: { userId: '456', pageUrl: '...', slug: '...', mode: '...', userRole: 'admin', merchantId: 'merchant_123', ... }
 */
export const getCommonSegmentProperties = (
  user: RazorpayUser | null = (window as any).rzp_user,
  config: Config = {},
): Record<string, any> => {
  if (!user) {
    // Skip properties if the session is expired or user details are unavailable
    return {};
  }

  const mode = getMode(user.id);

  const { addUserProperties = false } = config;

  let userProperties: Record<string, any> = {};

  if (addUserProperties) {
    userProperties = {
      business_type: user.business_type,
      activation_status: user.activated,
      current_activation_status: user.activation_status,
      previous_activation_status:
        user.activationStatusChangeLogs?.[user.activationStatusChangeLogs.length - 1]?.[
          'activation_status'
        ],
      user_business_category: user.business_category,
      user_business_sub_category: user.business_subcategory,
    };
  }

  const properties = {
    pageUrl: window.location.href.split('?')[0],
    slug: window.location.pathname,
    mode,
    userId: user.user?.id,
    userRole: user.role,
    merchantId: user.current,
    ...userProperties,
  };

  return {
    ...properties,
  };
};
