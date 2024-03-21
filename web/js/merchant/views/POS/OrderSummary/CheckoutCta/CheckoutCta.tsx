import React, { useContext, useState } from 'react';
import { ArrowRightIcon, Box, Button, Link, Text } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useQueryClient } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { compose } from 'redux';

import rzpLogo from 'assets/rzp_logo.jpg';
import { useSplitzService } from 'common/splitz';
import { User } from 'common/typings';
import {
  ACTIONS,
  DELIVERY_UNAVAILABLE_TEXT,
  POS_TERMS_AND_CONDITION_DATE,
} from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import {
  getPayloadForOrderCreate,
  checkIfPanIndiaLive,
  loadCheckoutForPos,
  preCheckoutAdditionalDetails,
} from 'merchant/views/POS/helpers';
import { createOrder, createActvationCase, getPincodeInfo } from 'merchant/views/POS/services';
import { ApiResponse, DeviceConfig, OrderDetailsItem } from 'merchant/views/POS/types';
import { showNotification } from 'merchant_common/reducers/notifications';

import ConfirmCheckout from './ConfirmCheckout';
import MissingShopImagesModal from './MissingShopImagesModal';

type CheckoutCtaProps = {
  isDisabled: boolean;
  isLoading: boolean;
  isSkipCheckout: boolean;
  showNotification: (args) => void;
};

type AdditionalInfoModal = {
  isRequired: boolean;
  url: string | null;
};

const CheckoutCta = ({
  isDisabled,
  isLoading,
  isSkipCheckout,
  showNotification,
}: CheckoutCtaProps): JSX.Element => {
  const queryClient = useQueryClient();
  const { state, dispatch } = useContext(PosDeviceStoreContext);

  const productPricingData = queryClient.getQueryData([
    'pos-pricing-plan',
  ]) as ApiResponse<DeviceConfig>;
  const razorpayKey = productPricingData?.data?.rzp_key;

  const { user, cartItems, deliveryAddresses } = state;
  const { created_at } = user || {};
  const [isCheckoutLoading, setIsCheckoutLoading] = useState<boolean>(false);
  const [isConfirmCheckoutOpen, setIsConfirmCheckoutOpen] = useState<boolean>(false);
  const [additionalInfoModal, setAdditionalInfoModal] = useState<AdditionalInfoModal>({
    isRequired: false,
    url: null,
  });

  const { abExperiments } = useSplitzService();
  const { omniChannelGtm } = abExperiments ?? {};
  const gtmCities = omniChannelGtm?.variables?.cities;
  const availableCities = typeof gtmCities === 'string' ? gtmCities.split(',') : [];

  const isPanIndiaLive = checkIfPanIndiaLive({ abExperiments });

  const isTermsAndConditionCheck = created_at ? created_at < POS_TERMS_AND_CONDITION_DATE : false;

  const handleOnPaymentFailure = (error?: string | null, dimissCheckout?: boolean) => {
    if (!!dimissCheckout) setIsCheckoutLoading(false);
    showNotification({ type: 'error', message: error ?? 'Payment Failed!' });
  };

  const redirectToOrderStatus = (orderId: string) => {
    window.location.assign(`/app/pos/order-status/${orderId}`);
  };

  const handleCheckoutSuccess = async (
    isCaseCreateRequired: boolean,
    orderData: OrderDetailsItem,
  ) => {
    showNotification({ type: 'success', message: 'Order Successful!' });
    try {
      if (isCaseCreateRequired) {
        const { data: activationData } = await createActvationCase();
        if (!activationData?.pos_activation_status) {
          throw new Error();
        }
        redirectToOrderStatus(orderData?.id);
      }
    } catch {
      handleOnPaymentFailure('Something went wrong.', true);
    } finally {
      dispatch({
        type: ACTIONS.UPDATE_CART,
        payload: {
          cartItems: [],
        },
      });
      setIsCheckoutLoading(false);
      if (!isCaseCreateRequired) {
        redirectToOrderStatus(orderData?.id);
      }
    }
  };

  const handleOnCheckoutClick = async () => {
    const { isRequired, url, isCaseCreateRequired } = preCheckoutAdditionalDetails({
      user: user as User,
    });
    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
      label: 'Confirm Address & Pay',
      whatsAppUpdates: 'No',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: 'Pre-checkout',
      section: 'Pre-checkout',
      subSection: 'Pre-checkout',
    });

    if (isRequired && url) {
      setAdditionalInfoModal(() => ({ isRequired, url }));
      return;
    }

    if (isSkipCheckout && !isConfirmCheckoutOpen) {
      setIsConfirmCheckoutOpen(true);
      return;
    }

    setIsConfirmCheckoutOpen(false);
    setIsCheckoutLoading(true);

    try {
      const createOrderPayload = await getPayloadForOrderCreate({ cartItems, deliveryAddresses });
      if (!createOrderPayload || !razorpayKey) throw new Error();

      if (!isPanIndiaLive) {
        const { data: pincodeInfo } = await getPincodeInfo(
          createOrderPayload?.delivery_address?.pin_code,
        );

        if (!pincodeInfo || !availableCities.includes(pincodeInfo.city)) {
          throw new Error(DELIVERY_UNAVAILABLE_TEXT);
        }
      }

      const { data } = await createOrder(createOrderPayload);
      if (!data?.id || !user) throw new Error();

      if (data.amount.total === 0 && !data.order_id) {
        handleCheckoutSuccess(isCaseCreateRequired, data);
        return;
      }

      await loadCheckoutForPos();
      const options = {
        notes: {
          type: 'Pos Device Store',
          merchant_id: user?.merchant?.id,
          device_order_id: data?.id,
        },
        key: razorpayKey,
        order_id: data.order_id,
        name: `Razorpay POS`,
        description: '18% GST included',
        image: rzpLogo,
        theme: {
          color: '#3005BF2',
        },
        modal: {
          confirm_close: true,
          ondismiss: () => handleOnPaymentFailure(null, true),
        },
        handler: () => handleCheckoutSuccess(isCaseCreateRequired, data),
      };
      const razorpayCheckout = new window.Razorpay(options);
      razorpayCheckout.open();
    } catch (error: unknown) {
      handleOnPaymentFailure(
        error instanceof Error && error?.message
          ? error.message
          : 'Something went wrong. Please try again',
        true,
      );
    } finally {
      setIsCheckoutLoading(true);
    }
  };

  return (
    <Box width="100%">
      <MissingShopImagesModal
        isOpen={additionalInfoModal.isRequired}
        externalUrl={additionalInfoModal.url}
        onClose={() => setAdditionalInfoModal({ isRequired: false, url: null })}
      />
      <ConfirmCheckout
        isOpen={isConfirmCheckoutOpen}
        onDismiss={() => setIsConfirmCheckoutOpen(false)}
        onSubmit={handleOnCheckoutClick}
      />
      {isTermsAndConditionCheck ? (
        <Text size="small" marginBottom="spacing.5" textAlign="center">
          By proceeding to pay, I agree to Razorpay POS{'  '}
          <Link
            size="small"
            href="https://razorpay.com/s/pos-machine-terms-of-use"
            testID="pos-terms-and-conditions-link"
          >
            Terms & Conditions
          </Link>{' '}
          and{' '}
          <Link
            size="small"
            href="https://razorpay.com/s/pos-machine-privacy-policy"
            testID="pos-privacy-policy-link"
          >
            Privacy Policy
          </Link>
        </Text>
      ) : null}
      <Button
        type="button"
        variant="primary"
        size="large"
        icon={ArrowRightIcon}
        iconPosition="right"
        testID="pos-checkout-cta"
        onClick={handleOnCheckoutClick}
        isDisabled={isDisabled}
        isLoading={isLoading || isCheckoutLoading}
        isFullWidth
      >
        Confirm Address & Pay
      </Button>
    </Box>
  );
};

export default compose(connect(null, { showNotification }))(CheckoutCta);
