import { authApiResponse } from '../../api/apiHelpers';
import { goToScreen, screenMap } from '../screenHelpers';

export const UNRECOGNIZED_ERROR = 'UNRECOGNIZED_ERROR';

export const handleErrorCodes = ({ error, navigate, locationQuery, updateUser }) => {
  if (error.message === authApiResponse.twoFactorRequired) {
    goToScreen({ screen: screenMap.twoFactorAuth, navigate, locationQuery });
  } else if (error.message === authApiResponse.twoFactorPasswordRequired) {
    updateUser({
      censoredEmail: error.internalData?.email ?? '',
    });
    goToScreen({ screen: screenMap.twoFactorAuthPassword, navigate, locationQuery });
  } else if (error.message === authApiResponse.twoFactorSetupRequired) {
    goToScreen({ screen: screenMap.setupTwoFactorAuth, navigate, locationQuery });
  } else if (
    error.message === authApiResponse.accountBlocked ||
    error.message === authApiResponse.otpLoginLocked
  ) {
    updateUser({
      isOwner: !!error.internalData?.is_owner,
    });
    goToScreen({ screen: screenMap.accountBlock, navigate, locationQuery });
  } else if (error.message === authApiResponse.accountNotFound) {
    return authApiResponse.accountNotFound;
  } else {
    return UNRECOGNIZED_ERROR;
  }
  return error.message;
};

export const gAuthBtnInitStatus = {
  LOADING: 'loading',
  SUCCESS: 'success',
  ERROR: 'error',
  COOKIE_ERROR: 'cookie_error',
};
export const gAuthInitCallStatus = {
  CALLED: 'init_invoked',
  NOT_CALLED: 'init_not_invoked',
};

export const gAuthTypes = {
  ONE_TAP: 'google-one-tap',
  BUTTON: 'google-button',
};
