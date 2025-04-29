/* eslint-disable object-property-newline */
/* eslint-disable @typescript-eslint/no-unnecessary-condition */
/* eslint-disable @typescript-eslint/no-unsafe-assignment */
/* eslint-disable @typescript-eslint/no-implicit-any-catch */
import { DASHBOARD_MODE } from '@libs/shared-types';
import axios, { AxiosRequestConfig, AxiosError, AxiosResponse } from 'axios';
import { ClientError, getPhpBaseUrlForClient, type ResponseWithErrors } from '@libs/shared-utils';
import { getMode } from '@federated/apps/shell/commonStore';

const restInstance = axios.create({});

/**
 * Only for UTs to mock with api handlers
 */
restInstance.defaults.baseURL = `${
  Boolean(typeof process?.env?.hostName != 'undefined')
    ? process.env.hostName
    : getPhpBaseUrlForClient()
}`;

/**
 * Type guard to check if an object is an AxiosError.
 * @param obj - The object to check.
 * @returns {boolean} - True if the object is an AxiosError.
 */
const isAxiosResponse = <T = unknown>(obj: unknown): obj is AxiosError<T> =>
  // @ts-expect-error - Fallback to generic error
  typeof obj === 'object' && typeof obj?.request === 'object';

/**
 * Fetch data using Axios, handling API errors and setting the correct mode.
 * @template T - The type of the response data.
 * @param {AxiosRequestConfig & { mode?: DASHBOARD_MODE }} options - Axios request options and mode.
 * @returns {Promise<T>} - A promise that resolves with the data or throws an error.
 * @throws {ClientError} - Throws a custom ClientError if the response contains errors.
 * @throws {AxiosError} - Throws an Axios error if Axios fails.
 */
export async function fetch<T>(
  options: AxiosRequestConfig & { mode?: DASHBOARD_MODE },
): Promise<T> {
  try {
    const response: AxiosResponse<{ data: T; error?: string }> = await restInstance({
      ...options,
      url: `/merchant/api/${options.mode ?? getMode()}/${options.url}`,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json, text/plain, */*',
        ...options?.headers,
      },
    });

    const result = response.data;

    // Handle successful response
    if (response.status >= 200 && response.status <= 204 && typeof result.data !== 'undefined') {
      return result.data;
    } else {
      const errorResult = typeof result === 'string' ? { error: result } : result;
      // @ts-expect-error - Fallback to generic error
      throw new ClientError({ ...errorResult, status: response.status });
    }
  } catch (error) {
    // Handle unauthorized access
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      document.body.dispatchEvent(
        new CustomEvent('NOT_AUTHENTICATED', {
          bubbles: true,
          detail: { continueAjax: () => {} },
        }),
      );
    }

    if (error instanceof ClientError) {
      throw error;
    } else if (isAxiosResponse(error)) {
      throw error;
    } else {
      // @ts-expect-error - Fallback to generic error
      throw new ClientError({ message: 'Unexpected error occurred', status: 400 });
    }
  }
}

/**
 * Fetch data using Axios for UCS, handling API errors and setting appropriate headers.
 * @template T - The type of the response data.
 * @param {AxiosRequestConfig & { mode?: DASHBOARD_MODE }} options - Axios request options and mode.
 * @returns {Promise<T>} - A promise that resolves with the data or throws an error.
 * @throws {ClientError} - Throws a custom ClientError if the response contains errors.
 * @throws {AxiosError} - Throws an Axios error if Axios fails.
 */
export async function fetchUCS<T>(
  options: AxiosRequestConfig & { mode?: DASHBOARD_MODE },
): Promise<T> {
  try {
    const response: AxiosResponse<T> = await restInstance({
      ...options,
      url: `/ucs/${options.url}`,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json, text/plain, */*',
        'X-Razorpay-Mode': getMode(),
        'X-Razorpay-Merchant-Id': window.rzp_user.current,
        'X-User-Id': window.rzp_user?.user?.id,
        ...options?.headers,
      },
    });

    const result = response.data;

    // Handle successful response
    if (response.status >= 200 && response.status <= 204 && result) {
      return result;
    } else {
      const errorResult = (typeof result === 'string'
        ? { error: result }
        : // @ts-expect-error - Fallback to generic error
        result?.error
        ? // @ts-expect-error - Fallback to generic error
          result.error
        : result) as unknown as ResponseWithErrors;
      throw new ClientError({ ...errorResult, status: response.status });
    }
  } catch (e) {
    const error = e as AxiosError<ResponseWithErrors>;
    if (error?.response?.status === 401) {
      document.body.dispatchEvent(
        new CustomEvent('NOT_AUTHENTICATED', {
          bubbles: true,
          detail: { continueAjax: () => {} },
        }),
      );
    }
    if (e instanceof ClientError) {
      throw e;
    } else if (isAxiosResponse(e)) {
      throw e;
    } else {
      // @ts-expect-error - Fallback to generic error
      throw new ClientError({ ...e, status: '400' } as ResponseWithErrors);
    }
  }
}
