import React, { useContext, useEffect, useRef } from 'react';
import { ArrowRightIcon, Box, Heading, Link, Spinner, Text } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useInfiniteQuery } from '@tanstack/react-query';
import EmptyOrderImg from 'assets/pos/icons/empty-order.svg';
import { connect } from 'react-redux';
import { useLocation, useNavigate } from 'react-router-dom';
import { compose } from 'redux';

import { ORDER_LIST_STATUS_TYPES } from 'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/ClientPOSOrderList/constants';
import { PosDeviceStoreContext } from '../context';
import { getOrderList, getSubmerchantOrderList } from '../services';
import { MainContainer } from './styles';
import { showNotification } from 'merchant_common/reducers/notifications';

import OrderListItem from './OrderListItem';

type OrderList = {
  pageSize?: number;
  showNotification: (args) => void;
};

// Note: The state value isRenderedFromPartnerRoute is for rendering this component inside
// Partner Dashboard to show Submerchant's POS Orders to their Partner and/or POS agents.
const OrderList = ({ showNotification, pageSize = 6 }: OrderList): JSX.Element => {
  const {
    state: { isRenderedFromPartnerRoute },
  } = useContext(PosDeviceStoreContext);
  const navigate = useNavigate();
  const nextPageTriggerRef = useRef(null);
  const location = useLocation();
  const {
    data,
    fetchNextPage: fetchOrderList,
    isFetching,
    hasNextPage,
    error,
  } = useInfiniteQuery({
    queryKey: ['orders'],
    retry: 0,
    retryDelay: 800,
    cacheTime: 1000 * 60 * 1,
    staleTime: Infinity,
    refetchOnWindowFocus: false,
    refetchOnMount: false,
    queryFn: ({ pageParam = 0 }) => {
      const payload = {
        skip: pageParam.toString(),
        count: pageSize.toString(),
        status: ORDER_LIST_STATUS_TYPES,
      };
      if (isRenderedFromPartnerRoute) return getSubmerchantOrderList(payload, location.pathname);
      else return getOrderList(payload);
    },
    getNextPageParam: (lastPage, allPages) => {
      if (lastPage?.order_list?.length < pageSize) return false;
      return allPages.length;
    },
    onError: () => {
      showNotification({ type: 'error', message: 'Something went wrong while fetching orders' });
    },
  });

  const orderListData = React.useMemo(
    () =>
      (data?.pages ?? [])
        .flatMap((item) => item?.order_list)
        .filter((orderListItem) => !!orderListItem),
    [data],
  );

  const handleOnNextPageTriggerVisible = (intersectionObserverEntry) => {
    const { isIntersecting } = intersectionObserverEntry[0];
    if (isIntersecting && hasNextPage) {
      fetchOrderList();
    }
  };

  useEffect(() => {
    const nextPageTriggerElement = nextPageTriggerRef.current;
    const observer = new IntersectionObserver(handleOnNextPageTriggerVisible, {
      threshold: 1,
    });
    if (nextPageTriggerElement) observer.observe(nextPageTriggerElement);

    return () => {
      if (nextPageTriggerElement) observer.unobserve(nextPageTriggerElement);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [nextPageTriggerRef, hasNextPage]);

  useEffect(() => {
    if (isRenderedFromPartnerRoute) return;
    analytics.track_EXPERIMENTAL(SignUpEvents.pageViewed, {
      pageType: 'Post Checkout - Order details',
      orderId: '',
    });
  }, [isRenderedFromPartnerRoute]);

  return (
    <MainContainer>
      {!isFetching && orderListData.length === 0 ? (
        <Box height="80vh" display="flex" alignItems="center" flexDirection="column">
          <Box
            maxWidth="400px"
            display="flex"
            alignItems="center"
            flexDirection="column"
            marginTop="10%"
          >
            <img src={EmptyOrderImg} height="130px" alt="empty order image" />
            <Text
              marginTop="spacing.6"
              marginBottom="spacing.3"
              textAlign="center"
              size="large"
              color="surface.text.gray.subtle"
            >
              No Order History
            </Text>
            <Text marginBottom="spacing.5" textAlign="center" color="surface.text.gray.subtle">
              It looks like you have not placed any orders yet. Explore our store and place your
              first order to get started.
            </Text>
            <Link
              icon={ArrowRightIcon}
              iconPosition="right"
              variant="button"
              isDisabled={isRenderedFromPartnerRoute}
              onClick={() => {
                if (isRenderedFromPartnerRoute) return;
                analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
                  label: 'Shop Now',
                  whatsAppUpdates: 'No',
                  section: 'Post-checkout',
                  subSection: 'List of Orders',
                  l1FunnelStage: 'Post Checkout',
                  l2FunnelStage: 'List of Orders',
                });

                navigate('/pos/catalog');
              }}
            >
              Shop now
            </Link>
          </Box>
        </Box>
      ) : null}
      {orderListData.length > 0 ? (
        <>
          <Box marginBottom="spacing.5">
            <Heading size="large">Your Orders</Heading>
          </Box>
          {orderListData.map((orderListItem) => (
            <OrderListItem
              key={orderListItem?.id}
              orderListItem={orderListItem}
              shouldDisableCTAs={isRenderedFromPartnerRoute}
            />
          ))}
        </>
      ) : null}
      {(hasNextPage || isFetching) && !error ? (
        <Box
          height="200px"
          display="flex"
          alignItems="center"
          justifyContent="center"
          ref={nextPageTriggerRef}
          testID="next-page-trigger-el"
        >
          <Spinner accessibilityLabel="order-list-spinner" size="large" />
        </Box>
      ) : null}
    </MainContainer>
  );
};

export default compose(connect(null, { showNotification }))(OrderList);
