import React, { useContext, useMemo, useState, useEffect } from 'react';
import {
  Alert,
  Amount,
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  Box,
  Button,
  Link,
  Text,
} from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import { CHECKOUT_ERRORS, PAGE_READ_SUCCESS_MS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { processPrecheckoutPricing, validatePrecheckout } from 'merchant/views/POS/helpers';
import {
  useBladeBreakpoints,
  useLatestOrder,
  useExecuteAfterDelay,
} from 'merchant/views/POS/hooks';
import { MainContainer } from 'merchant/views/POS/styles';

import CheckoutCta from './CheckoutCta';
import DeliveryAddresses from './DeliveryAddresses';
import OrderItems from './OrderItems';
import OrderPricing from './OrderPricing/OrderPricing';
import PosBreadcrumbs from 'merchant/views/POS/PosBreadcrumbs';

const OrderSummary = (): JSX.Element => {
  const { latestOrder, isLatestOrderLoading, latestOrderFetchError } = useLatestOrder();
  const { state } = useContext(PosDeviceStoreContext);
  const { cartItems, productDescriptions, user, isDeliveryAddressFormOpen } = state;
  const { isMobile } = useBladeBreakpoints();
  const [isViewDetailsSheetOpen, setIsViewDetailsSheetOpen] = useState<boolean>(false);

  const { orderProcessError, pricing } = useMemo(
    () => ({
      orderProcessError:
        validatePrecheckout({ cartItems, user, latestOrder, isDeliveryAddressFormOpen }) ??
        latestOrderFetchError,
      pricing: processPrecheckoutPricing({ cartItems, productDescriptions }),
    }),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [cartItems, latestOrder, latestOrderFetchError, isDeliveryAddressFormOpen],
  );

  const handlePageReadSuccess = () => {
    // Trigger pageReadSuccess event for page viewed after 15 seconds
    analytics.track_EXPERIMENTAL(SignUpEvents.pageReadSuccess, {
      pageType: 'Pre-checkout - Add POS Details',
    });
  };

  useExecuteAfterDelay({ callback: handlePageReadSuccess, delay: PAGE_READ_SUCCESS_MS });

  useEffect(() => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageViewed, {
      pageType: 'Pre-checkout - Add POS Details',
      orderId: '',
    });
  }, []);

  const onViewDetailsClick = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
      label: 'View details',
      whatsAppUpdates: 'No',
      section: 'Pre-checkout',
      subSection: 'Pre-checkout',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: 'Pre-checkout',
    });

    setIsViewDetailsSheetOpen(true);
  };

  const onContinueClick = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
      label: 'Continue',
      whatsAppUpdates: 'No',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: 'Pre-checkout',
      section: 'Pre-checkout',
      subSection: 'Payment Details',
    });

    setIsViewDetailsSheetOpen(false);
  };

  const isCheckoutDisabled = orderProcessError?.sev === 0 || isDeliveryAddressFormOpen;

  return (
    <MainContainer>
      <PosBreadcrumbs />
      <Box display={{ base: 'block', l: 'flex' }} gap="spacing.5" marginBottom="150px">
        <Box flex="0 0 60%">
          <OrderItems defaultIsExpanded />
          <DeliveryAddresses defaultIsExpanded />
        </Box>
        {!isMobile ? (
          <Box
            flex="0 0 40%"
            display="flex"
            flexDirection="column"
            alignItems="center"
            paddingX="spacing.5"
          >
            <Box width="100%" maxWidth="450px">
              <OrderPricing pricing={orderProcessError ? null : pricing} />
              {orderProcessError ? (
                <Alert
                  title={orderProcessError.title}
                  description={orderProcessError.description}
                  color={orderProcessError.sev === 0 ? 'negative' : 'notice'}
                  marginBottom="spacing.5"
                  isDismissible={
                    orderProcessError.type === CHECKOUT_ERRORS.ORDER_NOT_DELIVERABLE.type
                  }
                />
              ) : null}
              <CheckoutCta
                isDisabled={isCheckoutDisabled}
                isLoading={isLatestOrderLoading}
                isSkipCheckout={pricing.total === 0}
              />
            </Box>
          </Box>
        ) : (
          <Box
            backgroundColor="surface.background.gray.intense"
            width="100%"
            left="0px"
            right="0px"
            bottom="0px"
            position="fixed"
            padding="spacing.5"
            zIndex={10}
            elevation="highRaised"
          >
            <Box
              display="flex"
              alignItems="center"
              justifyContent="space-between"
              marginBottom="spacing.4"
            >
              <Box>
                <Text>Total Order Price</Text>
                <Amount
                  value={!orderProcessError ? pricing?.total ?? 0 : 0}
                  suffix="none"
                  isAffixSubtle={false}
                  testID="total-amount-mobile"
                  type="body"
                  size="large"
                  weight="semibold"
                />
              </Box>
              <Box>
                <Link
                  variant="button"
                  onClick={onViewDetailsClick}
                  isDisabled={!!orderProcessError}
                  testID="view-details-btn"
                >
                  View Details
                </Link>
                <BottomSheet
                  isOpen={isViewDetailsSheetOpen}
                  onDismiss={() => setIsViewDetailsSheetOpen(false)}
                  snapPoints={[0.8, 0.8, 1]}
                >
                  <BottomSheetBody>
                    <OrderPricing pricing={orderProcessError ? null : pricing} />
                  </BottomSheetBody>
                  <BottomSheetFooter>
                    <Button onClick={onContinueClick} size="large" isFullWidth>
                      Continue
                    </Button>
                  </BottomSheetFooter>
                </BottomSheet>
              </Box>
            </Box>
            {orderProcessError ? (
              <Alert
                title={orderProcessError.title}
                description={orderProcessError.description}
                color={orderProcessError.sev === 0 ? 'negative' : 'notice'}
                marginBottom="spacing.5"
                isDismissible={
                  orderProcessError.type === CHECKOUT_ERRORS.ORDER_NOT_DELIVERABLE.type
                }
              />
            ) : null}

            <CheckoutCta
              isDisabled={isCheckoutDisabled}
              isLoading={isLatestOrderLoading}
              isSkipCheckout={pricing.total === 0}
            />
          </Box>
        )}
      </Box>
    </MainContainer>
  );
};

export default OrderSummary;
