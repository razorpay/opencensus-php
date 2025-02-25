import { dashboardFetch } from '@libs/shared-utils';
import { getMode } from '@federated/apps/shell/commonStore';

/**
 * A scoped version of `dashboardFetch` intended for module federation enabled modules.
 *
 * @template ExpectedResponse - The expected response type from the API call.
 *
 * @param {Parameters<typeof dashboardFetch>[0]} params - The fetch parameters, either a URL or an object with options.
 *
 * @returns {Promise<ExpectedResponse>} - A promise resolving to the API response.
 *
 * Ref: `dashboardFetch` from `@libs/shared-utils`
 *
 * @example
 * merchantFetch<{ id: string, name: string }>({
 *   url: '/reports/configs',
 *   data: { id: 'merchant_123' },
 * }).then(response => {
 *   console.log(response);
 * });
 */
export const merchantFetch = <ExpectedResponse>(
  params: Parameters<typeof dashboardFetch>[0],
): Promise<ExpectedResponse> => {
  return dashboardFetch<ExpectedResponse>(params, { getMode });
};
