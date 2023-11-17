import {
  BANK_GATEWAYS,
  INTERNATIONAL_GATEWAYS,
  INCOMPLETE_GATEWAY,
  METHODS_MAP,
  TPV_OPTIONS,
} from 'merchant/views/Navigator/constants';

export function getTPVOptions(options) {
  return options.map((option) => ({ label: TPV_OPTIONS[option] || '', value: option }));
}

export function categorizeGateways(providersList = {}) {
  const categories = {
    aggregators: {},
    international_gateways: {},
    bank_gateways: {},
  };

  // filter gatewayKeys with empty payment methods
  const gatewayKeys = Object.keys(providersList)
    .filter(
      // exclude incomplete gateway integration
      (key) =>
        !INCOMPLETE_GATEWAY.includes(key) &&
        (providersList[key]?.['Payment Methods']?.data_value || []).length > 0, // include providers with non-empty payment methods.
    )
    .sort((a, b) => {
      // sort the gateway keys based on payment methods length
      const paymentMethodsA = providersList[a]?.['Payment Methods']?.data_value || [];
      const paymentMethodsB = providersList[b]?.['Payment Methods']?.data_value || [];
      return paymentMethodsB.length - paymentMethodsA.length;
    });

  if (gatewayKeys.length === 0) return {};

  gatewayKeys.forEach((gatewayKey) => {
    const gateway = providersList[gatewayKey];
    const paymentMethods = gateway?.['Payment Methods']?.data_value || [];
    const categorizedMethods = categorizePaymentMethods(paymentMethods);

    const category = BANK_GATEWAYS.includes(gatewayKey)
      ? 'bank_gateways'
      : INTERNATIONAL_GATEWAYS.includes(gatewayKey)
      ? 'international_gateways'
      : 'aggregators';

    addToCategory(categories[category], categorizedMethods, gatewayKey);
  });

  // filter category with no gateway
  const filteredCategories = Object.fromEntries(
    Object.entries(categories).filter(([_key, value]) => Object.keys(value).length !== 0),
  );

  return filteredCategories;
}

export function categorizePaymentMethods(paymentMethods) {
  const categorizedMethods =
    paymentMethods?.sort()?.map((method) => METHODS_MAP[method.toLowerCase()]) || [];

  if (categorizedMethods.length === 1) {
    return `${categorizedMethods[0]} only`;
  } else if (categorizedMethods.length === 2) {
    return `${categorizedMethods.join(' and ')}`;
  } else {
    return `${categorizedMethods.slice(0, -1).join(', ')}, and ${categorizedMethods.slice(-1)}`;
  }
}

export function addToCategory(categoryObj, categorizedMethods, gatewayKey) {
  categoryObj[categorizedMethods] = categoryObj[categorizedMethods] || [];
  categoryObj[categorizedMethods].push(gatewayKey);
}
