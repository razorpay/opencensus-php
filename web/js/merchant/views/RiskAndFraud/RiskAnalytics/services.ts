import { User } from 'common/typings';
import { getDeviceSource } from 'merchant/components/Support/getCommonSupportProperties';
import { TICKET_BASE_URL } from 'merchant/reducers/config';
import { merchantFetch } from 'merchant/utils/ajax';
import { getDataFromAPI } from 'merchant/views/Account/Profile/components/FIRC/utility';

import {
  DateRange,
  FetchRatiosResponse,
  FetchAnalyticsParams,
  FetchAnalyticsResponse,
  CreateFDTicketParams,
  FetchTableDataParams,
  FetchTableDataResponse,
} from './types';
import { calculateStats, generateChartData } from './utils';

export const fetchRatios = async ({
  startDate,
  endDate,
}: Omit<DateRange, 'preset'>): FetchRatiosResponse => {
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
          return [key, Number((value as number).toFixed(2))];
        }),
      );
    }

    return {}; // Implicitly typed as empty object
  } catch {
    throw new Error('Data fetch failed! Please try again');
  }
};

export const fetchAnalytics = async ({
  entity,
  metric,
  dateRange,
  interval,
  graphOptions,
}: FetchAnalyticsParams): FetchAnalyticsResponse => {
  const { startDate, endDate } = dateRange;
  const payload = {
    start_date: startDate,
    end_date: endDate,
    group_by: interval,
    entity,
  };
  try {
    const response = await merchantFetch({
      method: 'GET',
      url: 'payments_cross_border_live/v1/risk-analytics',
      mode: 'live',
      data: payload,
    });
    const { data } = getDataFromAPI(response);
    const stats = calculateStats(data);
    const chartData = generateChartData({
      entity,
      metric,
      graphOptions,
      queryData: data,
    });

    return {
      data,
      stats,
      chartData,
    };
  } catch {
    throw new Error('Data fetch failed! Please try again');
  }
};

export const createSupportTicketForBlockRule = async (
  user: User,
  { parameters, email, file, comments }: CreateFDTicketParams,
) => {
  const ticketData = new FormData();
  ticketData.set('email', user.email ?? '');
  ticketData.set('name', user.name ?? '');
  ticketData.set('phone', user.contact_mobile?.toString() ?? '');
  ticketData.set(
    'description',
    `Blacklist request for parameter ${parameters.toUpperCase()}. ${
      comments ? `Merchant comments :- ${comments}` : ''
    }`,
  );
  ticketData.set('subject', `[Merchant] Custom risk rule request`);
  ticketData.set('attachments[]', file);
  email.split(',').forEach((email) => {
    ticketData.append('cc_emails[]', email);
  });

  const customFieldData = {
    cf_merchant_id: user.id ?? '',
    cf_merchant_id_dashboard: `merchant_dashboard_${user.id}`,
    cf_ticket_owner: 'owner',
    cf_new_requester_item: 'Risk Rules',
    cf_new_requester_category: 'Merchant',
    cf_new_requester_sub_category: 'Account related assistance',
    cf_contact_number: user.contact_mobile?.toString() ?? '',
    cf_creation_source: getDeviceSource(),
  };

  for (const [field, value] of Object.entries(customFieldData)) {
    ticketData.set(`custom_fields[${field}]`, value);
  }

  const response = await merchantFetch({
    url: TICKET_BASE_URL,
    mode: 'live',
    method: 'POST',
    data: ticketData,
  });

  if (response?.data?.ticket_id) {
    return { created: true, ticketId: response?.data?.ticket_id };
  }

  return { created: false };
};

export const fetchTableData = async ({
  entity,
  dateRange,
  groupBy,
}: FetchTableDataParams): FetchTableDataResponse => {
  const { startDate, endDate } = dateRange;
  const payload = {
    start_date: startDate,
    end_date: endDate,
    group_by: groupBy,
    entity,
  };
  try {
    const response = await merchantFetch({
      method: 'GET',
      url: 'payments_cross_border_live/v1/risk-analytics',
      mode: 'live',
      data: payload,
    });
    const { data } = getDataFromAPI(response);
    return data;
  } catch {
    throw new Error('Data fetch failed! Please try again');
  }
};
