import axios from 'axios';
import isPlainObject from '@razorpay/universe-utils/isPlainObject';
import { trackApiLatency } from '../js/commonAnalytics';
import captureException, { captureSource } from '../shared/captureException';
import { twoFaErrors } from './apiHelpers';

const DEFAULT_ERROR = 'We could not process your request, Please try again.';
const DEFAULT_ERROR_CODE = 'BAD_REQUEST_UNKNOWN_ERROR_CODE';
const DEFAULT_ACTUAL_ERROR = 'UNKNOWN';

const REQUEST_START_HEADER_NAME = 'request-start-time';

let csrfToken = '';

/**
 *
 * @param {any} error
 * @returns {{errorMessage: string, internalData: any}}
 */
const getInternalErrorMessageAndData = (error) => {
  const errorCode = error.internal_error_code ?? DEFAULT_ERROR_CODE;
  const errorMessage = error.description ?? DEFAULT_ERROR;
  const internalData = error._internal?.user_details ?? {};

  // @TODO: remove the `twoFaErrors` variable itself and check against `error.code` for 2fa
  if (twoFaErrors.includes(errorCode)) {
    return {
      errorMessage: errorCode,
      errorCode,
      internalData,
    };
  }

  return {
    errorMessage,
    errorCode,
    internalData,
  };
};

const getErrorMessage = (res) => {
  let errorMessage = DEFAULT_ERROR;
  let errorCode = DEFAULT_ERROR_CODE;
  let actualError = DEFAULT_ACTUAL_ERROR;
  let internalData = {};

  if (res?.errors?.[0]) {
    const firstError = res.errors[0];

    if (isPlainObject(firstError) && firstError.internal_error_code) {
      ({ errorMessage, internalData, errorCode } = getInternalErrorMessageAndData(firstError));
      actualError = firstError.internal_error_code;
    } else {
      if (!firstError.includes('Internal Server Error')) {
        errorMessage = firstError;
      }
      actualError = firstError;
    }
  } else if (res?.message) {
    // Edge errors are in the format `{message: 'Too Many Requests'}`
    actualError = res.message;
  }

  return { errorMessage, actualError, errorCode, internalData };
};

// Create axios instance
const fetch = axios.create({
  baseURL: process.env.UNIVERSE_PUBLIC_SHIELD_API_BASEURL || '', // Use UNIVERSE_PUBLIC_SHIELD_API_BASEURL in env file to change base url for api calls
  withCredentials: true,
});

/**
 * Returns API Response time on giving response or error object
 *
 * @param {{config: {headers: Record<string, any>}}} response - response object or error object
 * @returns {number | null}
 */
const getApiResponseTime = (response) => {
  try {
    const apiStartTime = response.config.headers[REQUEST_START_HEADER_NAME];
    return parseFloat((performance.now() - apiStartTime).toFixed(2));
  } catch (err) {
    return null;
  }
};

fetch.defaults.headers['Content-Type'] = 'application/json';

const responseInterceptors = {
  /**
   * Success response interceptor to get csrf token from response and store it.
   * @param {object} response Response object
   * @returns Modified response object
   */
  success: (response) => {
    try {
      const apiResponseTime = getApiResponseTime(response);
      const { data } = response;

      const newCsrfTokenWithDate = response.headers['x-csrf-token'];
      const newCsrfToken = newCsrfTokenWithDate && newCsrfTokenWithDate.split(',')[0];

      if (newCsrfToken && csrfToken !== newCsrfToken) {
        csrfToken = newCsrfToken;
      }

      trackApiLatency({ route: response?.config?.url, apiResponseTime });

      if (!data.success) {
        const { errorMessage, errorCode, actualError, internalData } = getErrorMessage(data);
        const errorObj = {
          message: errorMessage,
          code: errorCode,
          internalData,
          statusCode: data.status,
          actualError,
          apiLatencyMs: apiResponseTime, // API Latency in Millisec,
          isApiError: true,
          flow: response?.config?.url,
        };

        captureException(errorObj, {
          flow: response?.config?.url,
          captureSource: captureSource.API,
        });
        return Promise.reject({ error: errorObj });
      }

      return { data: data.data };
    } catch (err) {
      captureException(err);
      return Promise.reject({ error: { message: DEFAULT_ERROR, actualError: err } });
    }
  },
  /**
   * Failure response interceptor.
   * @param {Error} error error object
   * @returns Rejected promise with modified error
   */
  failure: (error) => {
    try {
      const apiResponseTime = getApiResponseTime(error);
      const { errorMessage, errorCode, internalData, actualError } = getErrorMessage(error);
      trackApiLatency({ route: error?.config?.url, apiResponseTime });

      const errorObj = {
        message: errorMessage,
        code: errorCode,
        internalData,
        actualError,
        apiLatencyMs: apiResponseTime,
        isApiError: true,
        flow: error?.config?.url,
      };

      captureException(errorObj, { flow: error?.config?.url, captureSource: captureSource.API });

      return Promise.reject({ error: errorObj });
    } catch (err) {
      captureException(err);
      return Promise.reject({ error: { message: DEFAULT_ERROR, actualError: err } });
    }
  },
};

fetch.interceptors.response.use(responseInterceptors.success, responseInterceptors.failure);

/**
 * Request interceptor to add csrf token to all requests
 * @param {object} config Axios config
 * @returns modified Axios config
 */
const requestInterceptor = (config) => {
  const method = config.method;
  config.headers[REQUEST_START_HEADER_NAME] = performance.now();

  if (method !== 'get') {
    if (csrfToken) {
      // Add csrf token for all outgoing requests
      config.headers[method]['X-CSRF-TOKEN'] = csrfToken;
    }
  }

  return config;
};

fetch.interceptors.request.use(requestInterceptor);

const makeRequest = {
  get: (url, data, opts = {}) => {
    return fetch.get(url, { headers: { ...opts.headers }, params: data });
  },
  post: (url, data, opts = {}) => {
    return fetch.post(url, data, { headers: { ...opts.headers } });
  },
  patch: (url, data, opts = {}) => {
    return fetch.patch(url, data, { headers: { ...opts.headers } });
  },
  put: (url, data, opts = {}) => {
    return fetch.put(url, data, { headers: { ...opts.headers } });
  },
};

export default makeRequest;
