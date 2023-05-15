// utils
import { merchantFetch } from 'merchant/utils/ajax';
import { capitalize } from 'common/utils/rzp-utils';
///- utils

// types
import {
  BuyerAddressType,
  StateReturnType,
} from 'merchant/views/Transactions/B2bPayments/components/BuyerAddressModal/types';
///- types

export const getStatesWithCountryCode = (
  (cache: { [x: string]: Promise<StateReturnType> }) => (countryCode: string) => {
    if (cache[countryCode] !== undefined) {
      return cache[countryCode];
    }

    const request: Promise<StateReturnType> = merchantFetch({
      url: `states/${countryCode.toLowerCase()}/proxy`,
      mode: 'live',
    })
      .then((response) => {
        return Array.isArray(response?.data?.states)
          ? response.data.states.map((state) => {
              return {
                value: state.stateCode,
                label: capitalize(state.stateName),
              };
            })
          : [];
      })
      .catch(() => []);

    cache[countryCode] = request;
    return request;
  }
)({});

export const getBuyerAddressForPayment = (paymentId: string): Promise<BuyerAddressType | null> => {
  return merchantFetch({
    url: `b2b-exports/${paymentId}/address`,
  })
    .then((res) => {
      if (res?.data && typeof res?.data === 'object') {
        const { line1, city, country, name } = res.data;
        let { state, zipcode } = res.data;

        // Prase State from zipcode
        if (zipcode.indexOf(' ') > -1) {
          [state, zipcode] = zipcode.split(/\s+/);
        }
        return {
          line1: line1 ?? '',
          city: city ?? '',
          country: (country ?? '').toUpperCase(),
          name: name ?? '',
          state: state ?? '',
          zipcode: zipcode ?? '',
        };
      }
      return null;
    })
    .catch(() => null);
};

export const saveBuyerAddressForPayment = (
  paymentId: string,
  address: BuyerAddressType,
): Promise<{ success?: boolean; errors?: string[] }> => {
  return merchantFetch({
    url: `b2b-exports/${paymentId}/address`,
    method: 'PUT',
    data: address,
  });
};
