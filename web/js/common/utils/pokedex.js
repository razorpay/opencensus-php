import { groupBy, arrayToObject } from '@libs/shared-utils';

export const OTHERS = 'Others';
export const MOBILE_SDK = 'Mobile SDK';
export const BROWSER = 'Browser';
export const DESKTOP = 'Desktop';
export const MOBILE = 'Mobile';
export const ANDROID = 'Android';
export const IOS = 'iOS';
export const EMANDATE = 'e-Mandate';

export const globalGroupTitleMap = {
  upi: 'UPI',
  emi: 'EMI',
  ios: IOS,
  emandate: EMANDATE,
};

const platformsMap = {
  '1': BROWSER,
  '2': MOBILE_SDK,
};

const deviceMap = {
  '1': DESKTOP,
  '2': MOBILE,
  '3': MOBILE,
};

const osMap = {
  '4': ANDROID,
  '5': IOS,
};

export const platformGroupingVals = ['platform', 'os', 'device'];

export const getPlatformName = value => {
  return platformsMap[value] || OTHERS;
};

export const getDeviceName = value => {
  return deviceMap[value] || OTHERS;
};

export const getOsName = value => {
  return osMap[value] || OTHERS;
};

export const derivePlatformName = ({ platform, device, os }) => {
  const platformName = getPlatformName(platform);

  if (platformName === OTHERS) {
    return OTHERS;
  }

  if (platformName === BROWSER) {
    return getDeviceName(device);
  }

  if (platformName === MOBILE_SDK) {
    return getOsName(os);
  }

  return OTHERS;
};

export const groupByPlatform = records => {
  const groupedData = {},
    _groupedData = groupBy(records, 'platform');

  /*
   * we need to show the following categories
   * 1) Desktop - Platform is Browser and device is Desktop
   * 2) mWeb - Platform is Browser and device is Mobile
   * 3) Android - Platform is Mobile SDK and OS is Andorid
   * 4) IOS - Platform is Mobile SDK and OS is IOS
   * 5) Others - Everything else apart from ^
   */
  Object.keys(_groupedData).forEach(platformValue => {
    const item = _groupedData[platformValue],
      platformName = derivePlatformName({
        platform: platformValue,
      });

    if (!Array.isArray) {
      return;
    }

    item.forEach(item => {
      const platformName = derivePlatformName(item),
        data = groupedData[platformName];

      if (!data) {
        return (groupedData[platformName] = [item]);
      }

      return data.push(item);
    });
  });

  return groupedData;
};

export const oldestTransactionQuery = {
  filters: {
    default: [],
  },
  aggregations: {
    records: {
      agg_type: 'oldest',
      details: {
        index: 'payments',
        limit: 1,
        result_fields: ['created_at'],
      },
    },
  },
};

export const getDefaultFilter = (startTime, endTime) => {
  return {
    created_at: {
      gte: startTime,
      lte: endTime,
    },
  };
};

export const getDefaultPaymentFilter = (startTime, endTime) => {
  return {
    ...getDefaultFilter(startTime, endTime),
    authorized_at: {
      gt: 0,
    },
  };
};

export const groupCommissionListData = data => {
  const groupedData = {};
  Object.keys(data).forEach(key => {
    groupedData[key] = arrayToObject(data[key].result, formatData);
  });

  if (data.limit) {
    return data;
  }

  return Object.keys(groupedData.activeMerchants)
    .map(timestamp => ({
      timestamp,
      activeMerchants: groupedData.activeMerchants[timestamp],
      earnings: groupedData.earnings[timestamp],
      transactionVolume: groupedData.transactionVolume[timestamp],
      transactions: groupedData.transactions[timestamp],
      id: timestamp, // to avoid unwanted lumination of rows
    }))
    .sort((a, b) => b.timestamp - a.timestamp);

  function formatData({ timestamp, value }) {
    return { key: timestamp, value };
  }
};

export const groupSingleDayCommissionData = data => {
  const fields = [
    'baseEarnings',
    'addonEarnings',
    'baseTax',
    'addonTax',
    'transactionVolume',
    'activeMerchants',
    'transactions',
  ];

  return fields.reduce(
    (formattedData, field) => ({
      ...formattedData,
      ...formatData(field),
    }),
    {}
  );

  function formatData(field) {
    return {
      [field]: (data[field].result[0] || {}).value || 0,
    };
  }
};
