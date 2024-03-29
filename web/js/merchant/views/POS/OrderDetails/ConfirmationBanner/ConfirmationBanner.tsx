import React, { useEffect } from 'react';
import { Box, Text, Heading } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import moment from 'moment';
import Lottie from 'react-lottie';
import { useParams } from 'react-router-dom';

import ConfirmIcon from 'assets/pos/icons/orderConfirm.svg';
import OrderDetailsCTA from 'merchant/views/POS/OrderDetails/OrderDetailsCTA';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';

import OrderConfirmationLottie from './confirmationAnimation.json';
import { BannerOverlay, ConfirmationBannerContainer } from './styles';

type ConfirmationBanner = {
  arrivingDate: number;
};

const lottieDefaultOptions = {
  loop: 20,
  autoplay: true,
  speed: 2,
  animationData: OrderConfirmationLottie,
  rendererSettings: {
    preserveAspectRatio: 'xMidYMid slice',
  },
};

const ConfirmationBanner = ({ arrivingDate }: ConfirmationBanner): JSX.Element => {
  const { orderId } = useParams();
  const { isMobile } = useBladeBreakpoints();

  useEffect(() => {
    if (orderId) {
      analytics.track_EXPERIMENTAL(SignUpEvents.pageViewed, {
        pageType: 'Order Confirmation',
        orderId,
      });
    }
  }, [orderId]);

  return (
    <ConfirmationBannerContainer>
      <BannerOverlay />
      <Box
        position="absolute"
        top="0px"
        maxWidth="1200px"
        zIndex={0}
        display="flex"
        width="100%"
        justifyContent={{ base: 'center', l: 'space-between' }}
      >
        <Box left="0px">
          <Lottie options={lottieDefaultOptions} width="100%" />
        </Box>
        <Box right="0px" display={{ base: 'none', l: 'block' }}>
          <Lottie options={lottieDefaultOptions} width="50%" />
        </Box>
      </Box>
      <img src={ConfirmIcon} alt="Order Confirmed Icon" height="80px" />
      <Heading color="feedback.text.positive.intense" size="large">
        Your order is successfully placed!
      </Heading>
      <Text textAlign="center" color="surface.text.gray.subtle">
        Your Order <b>{orderId}</b> has successfully been placed with us
      </Text>
      <Text marginBottom="spacing.7" textAlign="center" color="surface.text.gray.subtle">
        Arriving by: <b>{moment.unix(arrivingDate).format('MMMM DD, YYYY')}</b>
      </Text>
      {!isMobile ? (
        <Box display="flex" zIndex={3}>
          <OrderDetailsCTA />
        </Box>
      ) : (
        <Box
          position="fixed"
          zIndex={3}
          bottom="spacing.0"
          left="spacing.0"
          right="spacing.0"
          backgroundColor="surface.background.gray.intense"
          padding="spacing.4"
        >
          <OrderDetailsCTA />
        </Box>
      )}
    </ConfirmationBannerContainer>
  );
};

export default ConfirmationBanner;
