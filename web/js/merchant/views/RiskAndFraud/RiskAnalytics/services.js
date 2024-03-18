import { merchantFetch } from 'merchant/utils/ajax';

export const fetchRatios = async ({ startDate, endDate }) => {
  const payload = { start_date: startDate, end_date: endDate };
  try {
    const response = await merchantFetch({
      method: 'GET',
      url: 'payments_cross_border_live/v1/risk-ratios',
      mode: 'live',
      data: payload,
    });
    if (response?.data?.data) {
      const { data } = response.data;
      return Object.fromEntries(
        Object.entries(data).map(([key, value]) => {
          return [key, Number(value.toFixed(2))];
        }),
      );
    }

    return {};
  } catch {
    throw new Error('Data fetch failed! Please try again');
  }
};
