import { dashboardFetch, getMode } from '@libs/shared-utils';

const fetchSettlementConfig = async () => {
  try {
    const response = await dashboardFetch(
      {
        url: 'settlements/dashboard/merchant_config/get',
        method: 'POST',
      },
      { getMode },
    );

    if (!response || response.error) {
      throw new Error('No Settlement Config data received');
    }
    return response;
  } catch (e: unknown) {
    console.warn('Failed to fetch');
    throw new Error('No Data Available for Settlement Config');
  }
};

export default fetchSettlementConfig;
