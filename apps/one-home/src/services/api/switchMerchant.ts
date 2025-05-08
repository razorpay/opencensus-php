import { ajax } from '@libs/shared-utils';
import { getMode } from '@federated/apps/shell/commonStore';
import errorService from '@razorpay/universe-cli/errorService';

export const switchMerchant = async (merchantId: string) => {
  try {
    await ajax(
      {
        url: `/settings/merchants/switch/${merchantId}`,
        appendModeInURL: false,
      },
      getMode,
    );
  } catch (error) {
    const rank = errorService.ErrorRank.P0;
    const tags = { module: 'One_Home_Switch_Merchant' };

    errorService.captureError(error, {
      tags,
      rank,
      extra: {
        info: error,
      },
    });
    console.error('Switch merchant failed:', error);
    throw error;
  }
};
