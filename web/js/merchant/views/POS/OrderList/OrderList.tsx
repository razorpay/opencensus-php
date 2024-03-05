import React, { useEffect, useRef } from 'react';
import {
  ArrowRightIcon,
  Box,
  Heading,
  Link,
  Spinner,
  Text,
  Title,
} from '@razorpay/blade/components';
import { useInfiniteQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { compose } from 'redux';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import EmptyOrderImg from 'assets/pos/icons/empty-order.svg';
import { getOrderList } from 'merchant/views/POS/services';
import { MainContainer } from 'merchant/views/POS/styles';
import { showNotification } from 'merchant_common/reducers/notifications';

import OrderListItem from './OrderListItem';

type OrderList = {
  pageSize?: number;
  showNotification: (args) => void;
};

const OrderList = ({ showNotification, pageSize = 6 }: OrderList): JSX.Element => {
  const navigate = useNavigate();
  const nextPageTriggerRef = useRef(null);

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
      };
      return getOrderList(payload);
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
    () => (data?.pages ?? []).flatMap((item) => item?.order_list),
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
    analytics.track_EXPERIMENTAL(SignUpEvents.pageViewed, {
      pageType: 'Post Checkout - Order details',
      orderId: '',
    });
  }, []);

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
            <Heading
              type="subtle"
              marginTop="spacing.6"
              marginBottom="spacing.3"
              textAlign="center"
            >
              No Order History
            </Heading>
            <Text type="subtle" marginBottom="spacing.5" textAlign="center">
              It looks like you have not placed any orders yet. Explore our store and place your
              first order to get started.
            </Text>
            <Link
              icon={ArrowRightIcon}
              iconPosition="right"
              variant="button"
              onClick={() => {
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
            <Title>Your Orders</Title>
          </Box>
          {orderListData.map((orderListItem) => (
            <OrderListItem key={orderListItem.id} orderListItem={orderListItem} />
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
