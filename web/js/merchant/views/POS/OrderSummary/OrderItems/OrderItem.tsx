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

  return (
    <Box
      display="flex"
      alignItems="center"
      marginBottom={'spacing.5'}
      marginX={isListItem || isOrderDetails ? 'spacing.0' : 'spacing.5'}
      marginTop={isListItem || isOrderDetails ? 'spacing.0' : 'spacing.5'}
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
          isOrderDetails ? 'surface.background.gray.intense' : 'surface.background.gray.moderate'
        }
        borderRadius="large"
        marginRight="spacing.4"
      />
      <Box>
        <Box display={{ base: 'block', l: 'flex' }} alignItems="center">
          <Text size={isMobile ? 'medium' : 'large'}>{productTitle}</Text>
          {!isMobile && PLAN_NAME_MAPPINGS[plan] ? (
            <Divider orientation="vertical" marginX="spacing.3" variant="muted" thickness="thin" />
          ) : null}
          <Text
            weight="regular"
            size={isMobile ? 'medium' : 'large'}
            color="surface.text.gray.subtle"
          >
            {PLAN_NAME_MAPPINGS[plan]}
          </Text>
        </Box>
        {isListItem && !isMobile ? (
          <Text marginRight="spacing.1" color="surface.text.gray.subtle">
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
          <Text textAlign="right" marginRight="spacing.1" color="surface.text.gray.subtle">
            Qty: {quantity}
          </Text>
        ) : null}
        {!isListItem ? (
          <Amount
            value={total.value}
            isAffixSubtle={false}
            suffix="none"
            type="body"
            size="large"
          />
        ) : null}
      </Box>
    </Box>
  );
};

export default OrderItem;
