import { ajax } from '@libs/shared-utils';
import { getMode } from '@federated/apps/shell/commonStore';
import errorService from '@razorpay/universe-cli/errorService';

export const logout = async () => {
  try {
    await ajax(
      {
        method: 'post',
        url: '/user/logout',
        appendModeInURL: false,
      },
      getMode,
    );
  } catch (error) {
    const rank = errorService.ErrorRank.P0;
    const tags = { module: 'One_Home_Logout' };

    errorService.captureError(error, {
      tags,
      rank,
      extra: {
        info: error,
      },
    });
    console.error('Logout failed:', error);
  }
};
