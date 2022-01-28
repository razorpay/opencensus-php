import { merchantFetch } from 'merchant/utils/ajax';
import formatServiceabilityPayload from 'merchant/reducers/magicCheckout/shipping_services/formatters';

const REDUCER_NAMESPACE = 'shipping_services';

export const ACTIONS = {
  CREATE_SHIPPING_PROVIDERS: `${REDUCER_NAMESPACE}:CREATE:SHIPPING_PROVIDERS`,
  CREATE_SHIPPING_PROVIDERS_ERROR: `${REDUCER_NAMESPACE}:CREATE:SHIPPING_PROVIDERS::ERROR`,
  CREATE_SHIPPING_PROVIDERS_SUCCESS: `${REDUCER_NAMESPACE}:CREATE:SHIPPING_PROVIDERS::SUCCESS`,
  FETCH_SHIPPING_PROVIDERS: `${REDUCER_NAMESPACE}:FETCH:SHIPPING_PROVIDERS`,
  FETCH_SHIPPING_PROVIDERS_PENDING: `${REDUCER_NAMESPACE}:FETCH:SHIPPING_PROVIDERS::PENDING`,
  FETCH_SHIPPING_PROVIDERS_SUCCESS: `${REDUCER_NAMESPACE}:FETCH:SHIPPING_PROVIDERS::SUCCESS`,
  DELETE_SHIPPING_PROVIDERS: `${REDUCER_NAMESPACE}:DELETE:SHIPPING_PROVIDERS`,
  DELETE_SHIPPING_PROVIDERS_SUCCESS: `${REDUCER_NAMESPACE}:DELETE:SHIPPING_PROVIDERS::SUCCESS`,
  FETCH_SHIPPING_METHOD_PROVIDERS: `${REDUCER_NAMESPACE}:FETCH:SHIPPING_METHOD_PROVIDERS`,
  FETCH_SHIPPING_METHOD_PROVIDERS_ERROR: `${REDUCER_NAMESPACE}:FETCH:SHIPPING_METHOD_PROVIDERS::ERROR`,
  FETCH_SHIPPING_METHOD_PROVIDERS_SUCCESS: `${REDUCER_NAMESPACE}:FETCH:SHIPPING_METHOD_PROVIDERS::SUCCESS`,
  CREATE_SHIPPING_METHOD_PROVIDERS: `${REDUCER_NAMESPACE}:CREATE:SHIPPING_METHOD_PROVIDERS`,
  CREATE_SHIPPING_METHOD_PROVIDERS_PENDING: `${REDUCER_NAMESPACE}:CREATE:SHIPPING_METHOD_PROVIDERS::PENDING`,
  CREATE_SHIPPING_METHOD_PROVIDERS_SUCCESS: `${REDUCER_NAMESPACE}:CREATE:SHIPPING_METHOD_PROVIDERS::SUCCESS`,
  DELETE_SHIPPING_METHOD_PROVIDERS: `${REDUCER_NAMESPACE}:DELETE:SHIPPING_METHOD_PROVIDERS`,
  DELETE_SHIPPING_METHOD_PROVIDERS_SUCCESS: `${REDUCER_NAMESPACE}:DELETE:SHIPPING_METHOD_PROVIDERS::SUCCESS`,
  MODAL_FLAG_MODIFY: `${REDUCER_NAMESPACE}:MODAL_FLAG::MODIFY`,
  USER_SHIPPING_METHOD_UPDATE: `${REDUCER_NAMESPACE}:USER_SHIPPING_METHOD::UPDATE`,
  USER_SHIPPING_METHOD_RESET: `${REDUCER_NAMESPACE}:USER_SHIPPING_METHOD::RESET`,
};

export const createShippingProviders = (email, password) => {
  const payload = {
    providerType: 'shiprocket',
    providerId: email,

    shiprocket: {
      auth: {
        user: email,
        password,
      },
    },
  };

  return {
    type: ACTIONS.CREATE_SHIPPING_PROVIDERS,
    payload: merchantFetch({
      url: `1cc/shipping_providers`,
      method: `post`,
      data: payload,
    }),
  };
};

export const fetchAggregators = () => ({
  type: ACTIONS.FETCH_SHIPPING_PROVIDERS,
  payload: merchantFetch({
    url: `1cc/shipping_providers`,
    method: `get`,
  }),
});

export const updateShippingMethods = (shippingMethodPayload) => {
  const formattedServiceabilityPayload = formatServiceabilityPayload(shippingMethodPayload);
  delete formattedServiceabilityPayload.id;

  return {
    type: ACTIONS.CREATE_SHIPPING_METHOD_PROVIDERS,
    payload: merchantFetch({
      url: `1cc/shipping_method_providers/${shippingMethodPayload.id}`,
      method: 'put',
      data: formattedServiceabilityPayload,
      headers: {
        'Content-Type': 'application/json',
      },
    }),
  };
};

export const createShippingMethods = (shippingMethodPayload) => ({
  type: ACTIONS.CREATE_SHIPPING_METHOD_PROVIDERS,
  payload: merchantFetch({
    url: `1cc/shipping_method_providers`,
    method: 'post',
    data: formatServiceabilityPayload(shippingMethodPayload),
    headers: {
      'Content-Type': 'application/json',
    },
  }),
});

export const fetchShippingMethods = (shipping_provider_id) => ({
  type: ACTIONS.FETCH_SHIPPING_METHOD_PROVIDERS,
  payload: merchantFetch({
    url: `1cc/shipping_method_providers`,
    method: `get`,
    data: { shipping_provider_id },
  }),
});

export const deleteShippingMethods = (shipping_method_provider_id) => ({
  type: ACTIONS.DELETE_SHIPPING_METHOD_PROVIDERS,
  payload: merchantFetch({
    url: `1cc/shipping_method_providers/${shipping_method_provider_id}`,
    method: `delete`,
  }),
});

export const deleteShippingProviders = (shipping_provider_id) => ({
  type: ACTIONS.DELETE_SHIPPING_PROVIDERS,
  payload: merchantFetch({
    url: `1cc/shipping_providers/${shipping_provider_id}`,
    method: `delete`,
  }),
});

export const toggleModalFlag = (flag) => ({
  type: ACTIONS.MODAL_FLAG_MODIFY,
  payload: { shouldCloseModal: flag },
});

export const updateUserShippingMethod = (key, value) => ({
  type: ACTIONS.USER_SHIPPING_METHOD_UPDATE,
  payload: { [key]: value },
});

export const resetUserShippingMethods = () => ({
  type: ACTIONS.USER_SHIPPING_METHOD_RESET,
});
