import React, { useContext } from 'react';
import { Amount, Box, Divider, Text } from '@razorpay/blade/components';

import { PLAN_NAME_MAPPINGS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getCartItemTotal, getProductFromProductDescriptions } from 'merchant/views/POS/helpers';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { OrderItem as OrderItemType } from 'merchant/views/POS/types';

type OrderItem = {
  orderItem: OrderItemType;
  isListItem?: boolean;
  isOrderDetails?: boolean;
};

const OrderItem = ({ orderItem, isListItem, isOrderDetails }: OrderItem): JSX.Element | null => {
  const { state } = useContext(PosDeviceStoreContext);
  const { isMobile } = useBladeBreakpoints();
  const { productDescriptions } = state;
  const product = getProductFromProductDescriptions({ code: orderItem.code, productDescriptions });

  if (!product || !product?.pricing) {
    return null;
  }

  const { plan, quantity } = orderItem;
  const { cartImage, productTitle, pricing } = product;

  const total = getCartItemTotal({ pricing, quantity, selectedPlan: plan });

  const margin = isMobile ? 'spacing.5' : 'spacing.7';

  return (
    <Box
      display="flex"
      alignItems="center"
      marginBottom={margin}
      marginX={isListItem || isOrderDetails ? 'spacing.0' : margin}
      marginTop={isListItem || isOrderDetails ? 'spacing.0' : margin}
      testID={`${orderItem.code}-${orderItem.plan}-order-item`}
    >
      <Box
        backgroundImage={`url("${cartImage}")`}
        backgroundRepeat="no-repeat"
        backgroundSize="cover"
        height={{ base: '65px', l: '70px' }}
        width={{ base: '65px', l: '70px' }}
        backgroundPosition="center center"
        backgroundColor={
          isOrderDetails
            ? 'surface.background.level2.lowContrast'
            : 'surface.background.level3.lowContrast'
        }
        borderRadius="large"
        marginRight="spacing.4"
      />
      <Box>
        <Box display={{ base: 'block', l: 'flex' }} alignItems="center">
          <Text size={isMobile ? 'medium' : 'large'}>{productTitle}</Text>
          {!isMobile ? (
            <Divider orientation="vertical" marginX="spacing.3" variant="normal" thickness="thin" />
          ) : null}
          <Text type="subtle" weight="regular" size={isMobile ? 'medium' : 'large'}>
            {PLAN_NAME_MAPPINGS[plan]}
          </Text>
        </Box>
        {isListItem && !isMobile ? (
          <Text type="subtle" marginRight="spacing.1">
            Qty: {quantity}
          </Text>
        ) : null}
      </Box>
      <Box
        marginLeft="auto"
        display="flex"
        flexDirection="column"
        justifyContent="right"
        alignItems="end"
      >
        {(isListItem && isMobile) || isOrderDetails ? (
          <Text type="subtle" textAlign="right" marginRight="spacing.1">
            Qty: {quantity}
          </Text>
        ) : null}
        {!isListItem ? (
          <Amount value={total} isAffixSubtle={false} suffix="none" size="heading-small" />
        ) : null}
      </Box>
    </Box>
  );
};

export default OrderItem;
