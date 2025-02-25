import { QueryClient } from '@tanstack/react-query';
import { mockNavigationData } from './mockData';
import { STAGE } from '@apps/shell/src/env';

export function mockAPI(responseData: any, status: boolean, delay = 1000): Promise<any> {
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      if (status) {
        resolve(responseData);
      } else {
        reject(responseData);
      }
    }, delay);
  });
}

export const fetchProducts = async (params) => {
  let data;
  try {
    if (STAGE === 'development' || STAGE === 'devstack') {
      data = await mockAPI(mockNavigationData, true, 500);
    } else {
      // integration with the actual API with params
    }
    return data;
  } catch (err: unknown) {
    if (err instanceof Error) {
      return new Promise((res, rej) => rej(null));
    }
    return new Promise((res, rej) => rej(null));
  }
};

export const fetchNavigation = (queryClient: QueryClient, params) =>
  queryClient.fetchQuery([`navigation-data`], fetchProducts.bind(null, params));
