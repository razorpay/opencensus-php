import { rest } from 'msw';

import {
  ANNOUNCEMENT_ASSET_DATA_RES,
  BANNER_ASSET_DATA_RES,
} from 'merchant/models/GrowthService/__test__/mocks/fixtures/GrowthService';
import { assetNames } from 'merchant/models/GrowthService/data';

const fetchAssetData = ({
  responseStatus = 200,
  apiLevelStatus = 200,
  gsLevelStatus = 200,
  delay = 0,
  doesChannelNotMatch = false,
  invalidateData = false,
}: {
  message?: string;
  responseStatus?: number;
  apiLevelStatus?: number;
  gsLevelStatus?: number;
  delay?: number;
  doesChannelNotMatch?: boolean;
  invalidateData?: boolean;
} = {}) =>
  rest.post('*/growth/assets', (req, res, ctx) => {
    const asset = req?.body?.['asset'];
    const channelId = req?.body?.['channel_id'];
    let assetData: undefined | Array<{ templates: Array<{ data: { id?: string } }> }>;

    if (
      responseStatus === 200 &&
      apiLevelStatus === 200 &&
      gsLevelStatus === 200 &&
      !doesChannelNotMatch
    ) {
      switch (asset) {
        case assetNames.ANNOUNCEMENT:
          assetData = ANNOUNCEMENT_ASSET_DATA_RES;
          break;

        case assetNames.BANNER:
          assetData = BANNER_ASSET_DATA_RES;
          break;

        default:
          break;
      }

      if (invalidateData && assetData?.[0]?.templates?.[0]?.data?.id)
        delete assetData[0].templates[0].data.id;
    }

    return res(
      ctx.status(responseStatus),
      ctx.json({
        status_code: apiLevelStatus,
        success: true,
        data: {
          status_code: gsLevelStatus,
          response: {
            channel_id: channelId,
            asset_data: assetData,
          },
        },
      }),
      ctx.delay(delay),
    );
  });

export { fetchAssetData };
