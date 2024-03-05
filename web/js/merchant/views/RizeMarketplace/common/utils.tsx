import React from 'react';
import {
  UseInfiniteQueryOptions,
  UseInfiniteQueryResult,
  UseQueryOptions,
  UseQueryResult,
  useInfiniteQuery,
  useQuery,
} from '@tanstack/react-query';
import { Navigate } from 'react-router-dom';

import { useApp } from 'common/context/App';
import { useSplitzService } from 'common/splitz';
import { merchantFetch } from 'merchant/utils/ajax';

import { WEBSITE_LINKS } from './constants';
import {
  APIError,
  FetchProductBySlugRequest,
  FetchProductsRequest,
  FetchProductsResponse,
  ProductResponse,
} from './types';

export const getMarketplaceProductQueryKey = (
  slug: string | undefined,
): [string, string | undefined] => ['rize-marketplace-product', slug];

const rizeFetch: typeof merchantFetch = async (...args) => {
  const result = await merchantFetch(...args);
  if (result.data.downstream_status_code >= 400) {
    return Promise.reject({
      code: 'UNKNOWN_ERROR_CODE',
      ...result.data,
    });
  }

  return result;
};

/**
 * Resolves a path to a fully qualified URL with origin of Rize frontend
 * @param url URL or URL path
 * @returns Fully qualified URL
 */
export const resolveToRizeUrl = (url: string): string => {
  // Use the appropriate `frontend-rize` origin based on environment
  const origin =
    process.env.PUBLIC_ENV === 'development'
      ? WEBSITE_LINKS.STAGE_RIZE_DASHBOARD
      : WEBSITE_LINKS.PROD_RIZE_DASHBOARD;

  return new URL(url, origin).toString();
};

const PAGE_SIZE = 10;
const WHATS_NEW_SIZE = 4;

export const useMarketplaceProduct = (
  slug: string | undefined,
  options: UseQueryOptions<
    ProductResponse,
    APIError,
    ProductResponse,
    [string, string | undefined]
  > = {},
): UseQueryResult<ProductResponse, APIError> => {
  const { user } = useApp();
  return useQuery({
    queryKey: getMarketplaceProductQueryKey(slug),
    queryFn: async () => {
      const { data } = await rizeFetch({
        url: `rize/dashboard/fetch_product_by_slug`,
        method: 'post',
        data: {
          slug,
          user_id: user.id,
        } as FetchProductBySlugRequest,
      });
      return data as ProductResponse;
    },
    // Data about a listing will not change often
    staleTime: 60 * 1000,
    ...options,
    enabled: slug !== undefined,
    refetchOnWindowFocus: false,
  });
};

export const useMarketplaceWhatsNew = (): UseQueryResult<FetchProductsResponse, APIError> => {
  const { user } = useApp();
  return useQuery({
    queryKey: ['rize-marketplace-whats-new'],
    queryFn: async () => {
      const { data } = await rizeFetch({
        url: 'rize/dashboard/fetch_products',
        method: 'post',
        data: {
          // Only WHATS_NEW_SIZE number of latest products are shown in the UI
          offset: 0,
          limit: WHATS_NEW_SIZE,
          user_id: user.id,
          filters: {
            category: [],
          },
          search: '',
        } as FetchProductsRequest,
      });
      return data as FetchProductsResponse;
    },
    refetchOnWindowFocus: false,
  });
};

export const useMarketplaceSimilarProducts = (
  productId: string | undefined,
  category: string | undefined,
): UseQueryResult<FetchProductsResponse, APIError> => {
  const { user } = useApp();
  return useQuery({
    queryKey: ['rize-marketplace-similar-products', productId, category],
    queryFn: async () => {
      const {
        data: { results },
      } = await rizeFetch({
        url: 'rize/dashboard/fetch_products',
        method: 'post',
        data: {
          filters: { category: [category] },
          // Fetch 1 extra product as one of them might be the current product itself
          offset: 0,
          limit: WHATS_NEW_SIZE + 1,
          search: '',
          user_id: user.id,
        } as FetchProductsRequest,
      });
      if (!results) throw new Error('Could not fetch similar products');

      // Remove the current product and narrow down to WHATS_NEW_SIZE number of results
      const filteredResults = (results as FetchProductsResponse['results'])
        .filter(({ id }) => productId !== id)
        .slice(0, WHATS_NEW_SIZE);

      return {
        results: filteredResults,
        total_count: filteredResults.length,
      } as FetchProductsResponse;
    },
    enabled: productId !== undefined && category !== undefined,
    refetchOnWindowFocus: false,
  });
};

export const useMarketplaceSearch = (
  filters: Pick<FetchProductsRequest, 'filters' | 'search'>,
  options: UseInfiniteQueryOptions<
    FetchProductsResponse,
    unknown,
    FetchProductsResponse,
    FetchProductsResponse,
    [string, Pick<FetchProductsRequest, 'filters' | 'search'>]
  > = {},
): UseInfiniteQueryResult<FetchProductsResponse> => {
  const { search, filters: filter } = filters;
  const isFilterApplied = !!filter?.category.length || !!search.length;
  const { user } = useApp();
  return useInfiniteQuery({
    queryKey: ['rize-marketplace-search', filters],
    queryFn: async ({ pageParam = 0 }) => {
      const { data } = await rizeFetch({
        url: 'rize/dashboard/fetch_products',
        method: 'post',
        data: {
          ...filters,
          limit: PAGE_SIZE,
          /**
           * When no filter is applied, offset the results by number of deals in "Whats new" / "New Deals" section.
           * When a filter is applied, this offset is removed.
           */
          offset: pageParam * PAGE_SIZE + (!isFilterApplied ? WHATS_NEW_SIZE : 0),
          user_id: user.id,
        } as FetchProductsRequest,
      });

      return data;
    },
    ...options,
    getNextPageParam: (lastPage, allPages) => {
      if (lastPage.total_count <= allPages.length * PAGE_SIZE) return undefined;
      return allPages.length;
    },
    cacheTime: 0,
    keepPreviousData: true,
    refetchOnWindowFocus: false,
  });
};

export const useRizeMarketplaceExperiment = (): boolean => {
  const {
    abExperiments: { rize_marketplace },
  } = useSplitzService();

  return rize_marketplace?.variables?.result === 'on';
};

/**
 * Only renders the passed component when the Rize Marketplace experiment on Splitz is enabled.
 * Otherwise, redirects to the home page
 */
export const withRizeMarketplaceExperimentGuard = <T,>(Component: React.FC<T>): React.FC<T> => {
  return (props) => {
    const isRizeMarketplaceEnabled = useRizeMarketplaceExperiment();
    if (!isRizeMarketplaceEnabled) return <Navigate to="/" replace />;

    return <Component {...props} />;
  };
};
