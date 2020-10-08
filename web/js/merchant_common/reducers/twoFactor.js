import { merge } from 'common/utils/immutable';
import createReducer from './createReducer';

const MARK_TWO_FACTOR_VERIFIED = 'MARK_TWO_FACTOR_VERIFIED';
const UPDATE_TWO_FACTOR_VERIFIED = 'UPDATE_TWO_FACTOR_VERIFIED';

import ajax, { merchantFetch } from 'merchant/utils/ajax';

/* Actions */

export const triggerTwoFactorVerificationOtp = () =>
  merchantFetch({
    url: 'users/2fa',
    method: 'POST',
  });

export const triggerOtpOnEmail = () =>
  triggerOtpForVerification({
    action: 'user_auth',
    medium: 'email',
  });

export const verifyOtpOnEmail = (data) =>
  merchantFetch({
    url: 'users/verify/mode/email',
    method: 'POST',
    data,
  });

export const verifyContactMobile = (data) => ({
  type: MARK_TWO_FACTOR_VERIFIED,
  payload: ajax({
    url: '/user/verify_contact',
    appendModeInURL: false,
    method: 'POST',
    data,
  }),
});

export const verifyTwoFactorOtp = (twoFactorOptions) => ({
  type: MARK_TWO_FACTOR_VERIFIED,
  payload: ajax({
    url: '/user/otp/verify',
    appendModeInURL: false,
    method: 'POST',
    data: {
      otp: twoFactorOptions.otp,
    },
  }),
});

export const updateTwoFactorVerified = ({ twoFactorVerified }) => ({
  type: UPDATE_TWO_FACTOR_VERIFIED,
  twoFactorVerified,
});

export const triggerOtpOnMobileForVerification = () =>
  triggerOtpForVerification({
    action: 'verify_contact',
    medium: 'sms',
  });

/****/

/* Action Handlers */
const markUserTwoFactorVerified = (state) =>
  merge(state, {
    data: {
      twoFactorVerified: true,
    },
  });

const saveTwoFactorVerificationState = (state, { twoFactorVerified }) =>
  merge(state, {
    data: { twoFactorVerified },
  });
/****/

/* reducer */
const initialState = {
  data: {
    twoFactorVerified: false,
  },
};

const handlers = {
  [`${MARK_TWO_FACTOR_VERIFIED}::SUCCESS`]: markUserTwoFactorVerified,
  [UPDATE_TWO_FACTOR_VERIFIED]: saveTwoFactorVerificationState,
};

export default createReducer({ initialState, handlers });
/****/

/* helpers */
function triggerOtpForVerification(data) {
  return merchantFetch({
    url: 'users/otp/send',
    method: 'POST',
    data,
    // Can't help it
    // This API works properly only in live mode
    // If you think it's test mode is fixed, you can remove the below line :P
    mode: 'live',
  });
}
/****/
