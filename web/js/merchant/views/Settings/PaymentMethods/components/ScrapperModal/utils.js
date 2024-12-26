import isEmpty from 'lodash/isEmpty';

import { WEBSITE_LINKS_MAPPING } from 'merchant/views/Settings/PaymentMethods/constants';

export const getInstrumentTat = (props) => {
  const { instrument, intermediateInstrument, leafInstrument, instrumentsTat } = props;
  const slugParts = ['pg', intermediateInstrument?.slug, leafInstrument?.slug, instrument?.slug];
  const requestSlug = slugParts
    .filter(Boolean)
    .join('.')
    .replace(/\.null|\.undefined/g, '')
    .replace(/\.meal-card/g, '.cards');

  return instrumentsTat && instrumentsTat[requestSlug];
};

function getReadableName(name) {
  if (!name) return 'NA';
  return WEBSITE_LINKS_MAPPING[name] || name;
}
export function groupByVerificationStatus(data) {
  if (!data || isEmpty(data)) return null;
  return data.reduce((acc, item) => {
    const key = item.verification_status ? 'Verified Details' : 'Missing/Unverified Details';
    if (!acc[key]) {
      acc[key] = [];
    }
    item.readableName = getReadableName(item?.name);
    acc[key].push(item);
    return acc;
  }, {});
}

export const getGroupedCollectInfo = (collectInfo) => {
  if (!collectInfo || isEmpty(collectInfo)) return [];

  const grouped = collectInfo.reduce((acc, item) => {
    const key = item.category;
    if (!acc[key]) {
      acc[key] = [];
    }
    acc[key].push(item);
    return acc;
  }, {});

  const sortedCategories = Object.keys(grouped).sort((a, b) => {
    if (a === 'Website Details') return -1;
    if (b === 'Website Details') return 1;
    return 0;
  });

  const sortedGrouped = sortedCategories.reduce((acc, category) => {
    acc[category] = grouped[category];
    return acc;
  }, {});

  return sortedGrouped;
};

export const updatedWebsiteDetailsValues = (collectInfo, scrapperInfo = []) => {
  return collectInfo.map((itemOne) => {
    const matchingItem = scrapperInfo.find((itemTwo) => itemTwo.name === itemOne.name);
    if (matchingItem) {
      return {
        ...itemOne,
        value: matchingItem.value,
        verification_status: matchingItem.verification_status || false,
      };
    }
    return { ...itemOne };
  });
};

export const isWebsiteDetailsPresent = (data) => {
  return data.some((item) => item.category === 'Website Details');
};

export const countUnverifiedWebsiteDetails = (data) => {
  return data.filter(
    (item) =>
      item.category === 'Website Details' &&
      (item.verification_status === false || !item.verification_status),
  ).length;
};

export const hasValidFieldsForCategory = (data, categoryName) => {
  return data
    .filter(
      (item) =>
        item.category === categoryName &&
        (item.verification_status === false || !item?.verification_status),
    )
    .every((item) => {
      if (typeof item.value === 'string') {
        return item.value.trim() !== '';
      }
      if (typeof item.value === 'number') {
        return !isNaN(item.value);
      }
      if (item.value instanceof Date) {
        return !isNaN(item.value.getTime());
      }
      return !!item.value;
    });
};

export const readableURL = (url) => {
  if (url.indexOf('://') !== -1) {
    let uri = url.split('//').pop();
    if (uri.startsWith('www.')) {
      uri = uri.replace('www.', '');
    }
    return uri;
  }
  return url;
};

export const isFieldValidUrl = (value = '', url) => {
  try {
    if (url === undefined) {
      return false;
    }
    const uri = new URL(value);
    const originalUri = new URL(url);

    const cleanOriginalSubDomain = originalUri.hostname.split('.').slice(-2).join('.');
    const cleanInputSubDomain = uri.hostname.split('.').slice(-2).join('.');

    return cleanOriginalSubDomain === cleanInputSubDomain;
  } catch {
    return false;
  }
};

export const getVerifiedNames = (data) => {
  return data.filter((item) => item.verification_status === true).map((item) => item.name);
};

export const getUnverifiedFields = (data) => {
  return data
    .filter((item) => item.verification_status !== true)
    .reduce((result, item) => {
      if (item.value) {
        result[item.name] = item.value;
      }
      return result;
    }, {});
};
