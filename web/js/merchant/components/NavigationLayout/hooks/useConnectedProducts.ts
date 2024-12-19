import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { constructListItems } from 'merchant/components/NavigationLayout/utils';
import { fetchUCSData } from 'merchant/containers/Home/RTUX/hooks/useUCSDataQuery';
import { determineNavigationType } from 'merchant/components/NavigationLayout/utils';
import { useStore } from 'shell/commonStore';
import isEmpty from 'lodash/isEmpty';
import { defaultConnectedProductsData, partnersFallbackData } from '../constants';
import { ExtendedListItems } from 'merchant/components/NavigationLayout/utils';
import { ProductAlias } from '../typings/component';
import { ProductType } from '../utils';

type ConnectedProducts = {
  loading: boolean | null;
  error: any;
  listItems: ExtendedListItems;
  listItemsByAlias: Record<ProductAlias, ProductType> | {};
  getProductAction: (productAlias: ProductAlias) => { type: string; value: string | undefined };
};

const useConnectedProducts = (): ConnectedProducts => {
  const isPartner = useStore((state) => state.session.user.isPartner?.());

  const { data, isLoading, isError, error } = useQuery(['topNavData'], {
    queryFn: async () => {
      try {
        const response = await fetchUCSData({
          alias: 'one_navigation',
        });

        if (!response || response.error || response.components.length === 0) {
          throw new Error('No products data available');
        }
        return response;
      } catch (e) {
        console.warn('Failed to fetch product details for top nav');
        throw new Error('No products data available');
      }
    },
    staleTime: 5 * 60 * 1000, // 5 minutes cache
    refetchOnWindowFocus: false,
    retry: false,
    networkMode: 'always',
  });

  const finalData = useMemo(() => {
    if (isError) {
      /**
       * Show Payments as default product when UCS data for product fetch fails
       */
      let fallbackComponents = [...defaultConnectedProductsData.components]; // Clone components array

      if (isPartner) {
        fallbackComponents = [...fallbackComponents, partnersFallbackData];
      }

      return {
        ...defaultConnectedProductsData,
        components: fallbackComponents,
      };
    }
    return data;
  }, [isError, data, isPartner]);

  const listItems = useMemo(() => {
    if (finalData) {
      return constructListItems(finalData.components || []);
    }
    return [];
  }, [finalData]);

  const listItemsByAlias = useMemo(() => {
    return listItems.reduce((acc, item) => {
      if (item.datum?.alias) {
        acc[item.datum.alias] = item;
      }
      return acc;
    }, {});
  }, [listItems]);

  const getProductAction = (productAlias) => {
    if (
      isLoading ||
      isEmpty(listItemsByAlias) ||
      !productAlias ||
      isEmpty(listItemsByAlias[productAlias])
    ) {
      return { type: 'none', value: '' };
    }

    const { datum: product } = listItemsByAlias[productAlias];
    return determineNavigationType(product.components || []);
  };

  return {
    loading: isLoading,
    error: isError ? error : null,
    listItems,
    listItemsByAlias,
    getProductAction,
  };
};

export default useConnectedProducts;
