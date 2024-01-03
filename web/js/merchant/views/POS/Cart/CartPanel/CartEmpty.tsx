import React from 'react';
import { ArrowRightIcon, Box, Button, Heading, Text } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import EmptyCartIcon from 'assets/pos/icons/empty-cart.svg';

type CartEmptyProps = {
  onShopMoreClick: () => void;
};

const CartEmpty = ({ onShopMoreClick }: CartEmptyProps): JSX.Element => {
  const onShopNowClicked = () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
      label: 'Shop Now',
      whatsAppUpdates: 'No',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'Cart',
      section: 'Cart',
      subSection: '', // check subSection from url
    });
    onShopMoreClick();
  };

  return (
    <Box
      height="80vh"
      width="100%"
      display="flex"
      alignItems="center"
      justifyContent="center"
      flexDirection="column"
    >
      <Box marginBottom="spacing.5">
        <img src={EmptyCartIcon} height="130px" />
      </Box>
      <Heading size="large" textAlign="center">
        Your cart is empty
      </Heading>
      <Text textAlign="center" type="subtle">
        Looks like you haven’t made your choices yet.
      </Text>
      <Button
        icon={ArrowRightIcon}
        iconPosition="right"
        marginTop="spacing.5"
        onClick={onShopNowClicked}
      >
        Shop Now
      </Button>
    </Box>
  );
};

export default CartEmpty;
