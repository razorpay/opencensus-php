import { ShippingProfile } from 'merchant/reducers/magicCheckout/shippingEngine/types';

export const verifyIfProfilesAreConfigured = (
  profiles: Record<string, ShippingProfile>,
): boolean => {
  for (const key in profiles) {
    if (profiles[key]) {
      const profile = profiles[key];
      if (!profile.zones?.length) {
        return false;
      } else {
        const hasMethods = profile?.zones?.every((zone) => zone.shipping_methods.length > 0);
        if (!hasMethods) {
          return false;
        }
      }
    }
  }
  return true;
};
