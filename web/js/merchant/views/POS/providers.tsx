import React, { useReducer, useEffect } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { useSplitzService } from 'common/splitz';
import { User } from 'common/typings';
import { merge } from 'common/utils/immutable';

import { ACTIONS, PosStoreInitialState } from './constants';
import { PosDeviceStoreContext } from './context';
import {
  constructProductDescription,
  fetchProductOffers,
  getAllDeliveryAddressFromLocalStorage,
  getCartFromLocalStorage,
  saveCartInBrowserStorage,
} from './helpers';
import { getProductPricingMap } from './services';
import {
  PosDeviceStoreState,
  PosDeviceStoreActionType,
  ProductPricingMap,
  ApiResponse,
  OfferConfig,
} from './types';

type PosDeviceStoreProviderProps = {
  children: React.ReactNode;
  init?: PosDeviceStoreState;
  user: User;
};

const reducer = (
  state: PosDeviceStoreState,
  action: PosDeviceStoreActionType,
): PosDeviceStoreState => {
  switch (action.type) {
    case ACTIONS.OPEN_CART:
      return merge(state, {
        isCartOpen: true,
      });
    case ACTIONS.CLOSE_CART:
      return merge(state, {
        isCartOpen: false,
      });
    case ACTIONS.UPDATE_CART: {
      const cartItems = action.payload?.cartItems ?? [];
      const userId = action.payload?.user?.id ?? state.user?.id;
      if (userId) {
        saveCartInBrowserStorage({
          cartItems,
          userId,
        });
      }
      return merge(state, {
        cartItems,
      });
    }
    case ACTIONS.SET_PRODUCT_DESCRIPTION:
      return merge(state, {
        productDescriptions: action.payload?.productDescriptions,
      });
    case ACTIONS.SET_USER:
      return merge(state, {
        user: action.payload?.user,
      });

    case ACTIONS.UPDATE_DELIVERY_ADDRESSES:
      return merge(state, {
        deliveryAddresses: action.payload?.deliveryAddresses,
      });
    case ACTIONS.SET_DELIVERY_ADDRESS_FORM_OPEN:
      return merge(state, {
        isDeliveryAddressFormOpen: action.payload?.isDeliveryAddressFormOpen,
      });
    default:
      return state;
  }
};

export const PosDeviceStoreProvider = ({
  children,
  init,
  user,
}: PosDeviceStoreProviderProps): JSX.Element => {
  const initialState = init ?? PosStoreInitialState;
  const [state, dispatch] = useReducer(reducer, initialState);

  const { abExperiments } = useSplitzService();
  const offersInfo = fetchProductOffers({ abExperiments });

  const onFetchProductPricing = (
    response: ApiResponse<Record<'configs', ProductPricingMap>>,
    offerConfig: Record<string, OfferConfig> | null,
  ) => {
    if (response?.data?.configs) {
      const { configs } = response?.data;
      const productDescriptions = constructProductDescription({
        pricingPlanDict: configs,
        offerConfig,
      });

      dispatch({
        type: ACTIONS.SET_PRODUCT_DESCRIPTION,
        payload: {
          productDescriptions,
        },
      });
    }
  };
  const { isLoading: isPricingPlanLoading } = useQuery({
    queryKey: ['pos-pricing-plan'],
    queryFn: getProductPricingMap,
    retry: 2,
    retryDelay: 800,
    cacheTime: 1000 * 60 * 1,
    staleTime: Infinity,
    refetchOnWindowFocus: false,
    refetchOnMount: 'always',
    onSuccess: (data) => onFetchProductPricing(data, offersInfo?.offers),
  });

  const populateDeliveryAddress = ({ user }) => {
    const deliveryAddresses = getAllDeliveryAddressFromLocalStorage({ user });
    dispatch({
      type: ACTIONS.UPDATE_DELIVERY_ADDRESSES,
      payload: {
        deliveryAddresses,
      },
    });
  };

  useEffect(() => {
    if (user?.id) {
      const initialCartState = init?.cartItems ?? getCartFromLocalStorage({ userId: user.id });
      populateDeliveryAddress({ user });
      if (initialCartState) {
        dispatch({
          type: ACTIONS.UPDATE_CART,
          payload: {
            cartItems: initialCartState,
            user,
          },
        });
      }
      dispatch({
        type: ACTIONS.SET_USER,
        payload: { user },
      });
    }
  }, [user, init]);

  return (
    <PosDeviceStoreContext.Provider
      value={{
        state: {
          ...state,
          isPricingPlanLoading,
        },
        dispatch,
      }}
    >
      {isPricingPlanLoading || (state.productDescriptions ?? []).length === 0 ? (
        <Box
          as="section"
          height="90vh"
          width="100%"
          display="flex"
          alignItems="center"
          justifyContent="center"
        >
          <Spinner accessibilityLabel="pos-store-spinner" size="xlarge" />
        </Box>
      ) : (
        children
      )}
    </PosDeviceStoreContext.Provider>
  );
};
