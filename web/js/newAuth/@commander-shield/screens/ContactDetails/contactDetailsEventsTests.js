import { authMethods } from '../screenHelpers';
import contactDetailsEvents from './contactDetailsEvents';

export const contactDetailsEventTests = {
  trackSubmitInitiate: (user, method = authMethods.EMAIL) => {
    expect(contactDetailsEvents.trackSubmitInitiate).toHaveBeenCalledWith(user, method);
  },
  trackSubmitSuccess: (user, method = authMethods.EMAIL) => {
    expect(contactDetailsEvents.trackSubmitSuccess(user, method));
  },
  trackCouponCodeSuccess: (user, coupon) => {
    expect(contactDetailsEvents.trackCouponCodeSuccess(user, coupon));
  },
  trackCouponCodeError: (coupon, error) => {
    expect(contactDetailsEvents.trackCouponCodeError(coupon, error));
  },
};
