import React, { useReducer, useEffect } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { useLocation } from 'react-router-dom';

import { useSplitzService } from 'common/splitz';
import { User } from 'common/typings';
import { merge } from 'common/utils/immutable';

import {
  ACTIONS,
  PosStoreInitialState,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/ClientPOSOrderList/constants';
import { PosDeviceStoreContext } from './context';
import {
  constructProductDescription,
  fetchProductOffers,
  getAllDeliveryAddressFromLocalStorage,
  getCartFromLocalStorage,
  isPosSoundboxEnabled,
  saveCartInBrowserStorage,
} from './helpers';
import { getProductPricingMap, getSubmerchantProductPricingMap } from './services';
import {
  PosDeviceStoreState,
  PosDeviceStoreActionType,
  ProductPricingMap,
  ApiResponse,
  OfferConfig,
  CartItem,
} from './types';

type PosDeviceStoreProviderProps = {
  children: React.ReactNode;
  init?: PosDeviceStoreState;
  user: User;
  isRenderedFromPartnerRoute?: boolean;
};

const reducer = (
  state: PosDeviceStoreState,
  action: PosDeviceStoreActionType,
): PosDeviceStoreState => {
  switch (action.type) {
    case ACTIONS.OPEN_CART:
      // @ts-expect-error
      return merge(state, {
        isCartOpen: true,
      });
    case ACTIONS.CLOSE_CART:
      // @ts-expect-error
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
      // @ts-expect-error
      return merge(state, {
        cartItems,
      });
    }
    case ACTIONS.SET_PRODUCT_DESCRIPTION:
      // @ts-expect-error
      return merge(state, {
        productDescriptions: action.payload?.productDescriptions,
      });
    case ACTIONS.SET_USER:
      // @ts-expect-error
      return merge(state, {
        user: action.payload?.user,
      });

    case ACTIONS.UPDATE_DELIVERY_ADDRESSES:
      // @ts-expect-error
      return merge(state, {
        deliveryAddresses: action.payload?.deliveryAddresses,
      });
    case ACTIONS.SET_DELIVERY_ADDRESS_FORM_OPEN:
      // @ts-expect-error
      return merge(state, {
        isDeliveryAddressFormOpen: action.payload?.isDeliveryAddressFormOpen,
      });
    default:
      return state;
  }
};

// Note: The prop isRenderedFromPartnerRoute is for rendering this component inside
// Partner Dashboard to show Submerchant's POS Orders to their Partner and/or POS agents.
export const PosDeviceStoreProvider = ({
  children,
  init,
  user,
  isRenderedFromPartnerRoute = false,
}: PosDeviceStoreProviderProps): JSX.Element => {
  const initialState = init ?? PosStoreInitialState;
  const [state, dispatch] = useReducer(reducer, initialState);

  const { abExperiments } = useSplitzService();
  const isSoundboxEnabled = isPosSoundboxEnabled({ abExperiments });
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
        isSoundboxEnabled,
      });

      dispatch({
        type: ACTIONS.SET_PRODUCT_DESCRIPTION,
        payload: {
          productDescriptions,
        },
      });
    }
  };
  const location = useLocation();
  const { isLoading: isPricingPlanLoading } = useQuery({
    queryKey: ['pos-pricing-plan'],
    queryFn: isRenderedFromPartnerRoute
      ? () => getSubmerchantProductPricingMap(location.pathname)
      : getProductPricingMap,
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
          isRenderedFromPartnerRoute,
          isSoundboxEnabled,
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
