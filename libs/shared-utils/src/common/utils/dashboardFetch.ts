/* eslint-disable import/order */
import type { AxiosRequestConfig } from 'axios';
import { commonAjax } from './commonAjax';
import { DASHBOARD_MODE } from '@libs/shared-types';
import { isFunction } from './isFunction';

export type DashboardFetchParams = Omit<AxiosRequestConfig, 'url'> & {
  mode?: DASHBOARD_MODE;
  accountId?: string;
  absUrl?: string;
} & (
    | { absUrl: string; url?: never } // When `absUrl` is present, `url` is not allowed
    | { absUrl?: never; url: string }
  ); // When `absUrl` is not present, `url` is required

interface DashboardFetchConfig {
  getMode: () => DASHBOARD_MODE;
}

/**
 * This utility function `dashboardFetch` is used for making HTTP requests across various dashboards.
 * It centralizes fetching logic for all dashboards by configuring it once per dashboard instance,
 * and then reusing the same configuration across different consumers scoped within that dashboard.
 *
 * The function accepts a URL or an object containing more specific fetch parameters and adds
 * headers or modifies the URL based on the dashboard's mode and account ID. It handles different modes
 * for making API requests based on the dashboard, e.g., `/merchant/api/[mode]/[url]`,
 * as well as absolute URLs when provided.
 *
 * @template InferredResponse - The type of the response expected from the `commonAjax` call.
 *
 * @param {string | DashboardFetchParams} params - The fetch parameters, either a URL string or an object
 * containing the URL and additional fetch options such as `mode`, `accountId`, etc.
 *
 * @param {DashboardFetchConfig} [config] - Optional configuration object with a function to get the current `DASHBOARD_MODE`.
 *
 * @returns {Promise<InferredResponse>} - Returns a promise resolving to the response of type `InferredResponse` from `commonAjax`.
 *
 * @throws {Error} If `mode` is not provided in the `params` or the `config` object, an error is thrown.
 *
 * @example
 * // Example 1: Basic Usage with URL string
 * dashboardFetch<{ id: number }, ResponseType>('/users', {
 *   getMode: () => DASHBOARD_MODE.LIVE,
 * });
 *
 * @example
 * // Example 2: Usage with accountId and headers
 * dashboardFetch<ResponseType>({
 *   url: '/users',
 *   accountId: 'acc_123456',
 *   headers: { 'Custom-Header': 'HeaderValue' }
 * }, {
 *   getMode: () => DASHBOARD_MODE.TEST,
 * });
 */
export const dashboardFetch = <InferredResponse>(
  params: string | DashboardFetchParams,
  config?: DashboardFetchConfig,
): Promise<InferredResponse> => {
  // If the `params` is a string, convert it to an object with the URL
  if (typeof params === 'string') {
    params = {
      url: params,
    } as DashboardFetchParams;
  }

  // Determine the mode to use for the API call
  let mode = params.mode;
  if (mode) {
    delete params.mode; // Remove `mode` from params if it is explicitly set
  } else if (isFunction(config?.getMode)) {
    mode = config?.getMode(); // Get mode from the configuration function
  } else {
    throw new Error('Missing Mode In Common Fetch!'); // Error if no mode is provided
  }

  // If `accountId` is provided, add it to the request headers
  if (params.accountId) {
    params.headers = {
      ...params.headers,
      'X-Razorpay-Account': params.accountId, // Add `X-Razorpay-Account` header
    };
  }

  delete params.accountId;

  // If the app name is 'businessbanking', add the `X-Origin-Product` header
  if (window.RZP?.appName === 'businessbanking') {
    params.headers = {
      ...params.headers,
      'X-Origin-Product': window.RZP.appHost, // Add `X-Origin-Product` header
    };
  }

  // Construct the URL based on the mode and whether an absolute URL is provided
  params.url = params.absUrl ? params.absUrl : `/merchant/api/${mode}/${params.url}`;

  // Remove the `absUrl` property after using it
  delete params.absUrl;

  // Make the AJAX request using the `commonAjax` utility
  return commonAjax<InferredResponse>(params);
};
