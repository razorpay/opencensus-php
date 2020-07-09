import { merge } from 'common/utils/immutable';
import createReducer from './createReducer';

const MARK_TWO_FACTOR_VERIFIED = 'MARK_TWO_FACTOR_VERIFIED';
const UPDATE_TWO_FACTOR_VERIFIED = 'UPDATE_TWO_FACTOR_VERIFIED';

/* Actions */

export const triggerTwoFactorVerificationOtp = asyncCall =>
  asyncCall({
    url: 'users/2fa',
    method: 'POST',
  });

export const verifyTwoFactorOtp = (twoFactorOptions, asyncCall) => ({
  type: MARK_TWO_FACTOR_VERIFIED,
  payload: asyncCall({
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

/****/

/* Action Handlers */
const markUserTwoFactorVerified = state =>
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
