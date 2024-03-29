import React from 'react';
import { Box, ShoppingCartIcon, Text, Button } from '@razorpay/blade/components';

import { OrderFooterWrapper } from './styled';

type Props = {
  items: number;
  onClickViewCart: () => void;
};
const OrderFooterSection = ({ items, onClickViewCart }: Props) => {
  return (
    <Box
      width={{
        l: '1200px',
        m: '100%',
        s: '100%',
      }}
    >
      <OrderFooterWrapper>
        <Box
          display="flex"
          flexDirection="row"
          justifyContent="space-between"
          alignItems="center"
          height="70px"
          borderTopWidth="thicker"
          borderColor="surface.border.gray.muted"
          padding="spacing.6"
        >
          <Box display="flex" flexDirection="row" alignItems="center">
            <Box>
              <ShoppingCartIcon size="xlarge" color="interactive.icon.gray.normal" />
            </Box>
            <Box paddingLeft="spacing.4">
              <Text weight="semibold" color="surface.text.gray.muted">{`${
                items || 0
              } Gift Card Program selected`}</Text>
            </Box>
          </Box>
          <Box>
            <Button
              isDisabled={!items}
              variant="primary"
              onClick={() => {
                onClickViewCart();
              }}
            >
              View Cart
            </Button>
          </Box>
        </Box>
      </OrderFooterWrapper>
    </Box>
  );
};

export default OrderFooterSection;
