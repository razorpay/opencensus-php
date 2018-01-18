export const OTHERS = 'Others';
export const MOBILE_SDK = 'Mobile SDK';
export const BROWSER = 'Browser';
export const DESKTOP = 'Desktop';
export const MOBILE = 'Mobile';
export const ANDROID = 'Android';
export const IOS = 'IOS';

export const globalGroupTitleMap = {
  upi: 'UPI',
  emi: 'EMI',
  ios: 'IOS',
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

export const groupBy = (records, colName) => {
  const result = {};

  records.forEach((record, index) => {
    if (!record.hasOwnProperty(colName)) {
      return;
    }

    const colValue = record[colName],
      colRecords = (result[colValue] = result[colValue] || []);

    colRecords.push(record);
  });

  return result;
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
