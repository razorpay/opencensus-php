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
          borderColor="surface.border.normal.lowContrast"
          padding="spacing.6"
        >
          <Box display="flex" flexDirection="row" alignItems="center">
            <Box>
              <ShoppingCartIcon size="xlarge" color="surface.action.icon.active.lowContrast" />
            </Box>
            <Box paddingLeft="spacing.4">
              <Text
                weight="bold"
                color="surface.text.subdued.lowContrast"
              >{`${items} Gift Card Program selected`}</Text>
            </Box>
          </Box>
          <Box>
            <Button variant="primary" onClick={() => onClickViewCart()}>
              View Cart
            </Button>
          </Box>
        </Box>
      </OrderFooterWrapper>
    </Box>
  );
};

export default OrderFooterSection;
