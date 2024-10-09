import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import EmptyCartImage from 'apps/pos/src/assets/EmptyCart.svg';

export const EmptyCart = (): JSX.Element => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      justifyContent="center"
      alignItems="center"
      marginTop="50%"
    >
      <img src={EmptyCartImage} alt="Empty Cart" width="102px" height="108px" />
      <Text weight="semibold" color="surface.text.gray.subtle" textAlign="center" size="large">
        All devices in your order are deployed!
      </Text>
    </Box>
  );
};
