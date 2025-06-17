import React from 'react';
import {
  Alert,
  Amount,
  Badge,
  Box,
  Button,
  Divider,
  Heading,
  Text,
} from '@razorpay/blade/components';
import moment from 'moment';
import { useNavigate } from 'react-router-dom';

import OrderItem from 'apps/pos/src/app/views/SelfServe/OrderSummary/OrderItems/OrderItem';
import { getOrderStatus } from 'apps/pos/src/app/views/SelfServe/helpers';
import { useBladeBreakpoints } from 'apps/pos/src/app/views/SelfServe/hooks';
import { OrderDetailsItem, ProductPlans } from 'apps/pos/src/app/views/SelfServe/types';

type OrderListItemProps = {
  orderListItem: OrderDetailsItem;
  shouldDisableCTAs: boolean;
};

const OrderListItem = ({
  orderListItem,
  shouldDisableCTAs,
}: OrderListItemProps): JSX.Element | null => {
  const { isMobile } = useBladeBreakpoints();
  const navigate = useNavigate();
  const { items } = orderListItem;
  const { id, amount } = orderListItem;

  const orderStatusMetaData = getOrderStatus(orderListItem);

  const handleViewDetailsClick = ({ id }): void => {
    if (shouldDisableCTAs) return;
    navigate(`/pos/orders/${id}`);
  };

  if ((items ?? []).length === 0 || !orderListItem) return null;

  return (
    <div
      data-testid={`order-list-item-${id}`}
      onClick={() =>
        !shouldDisableCTAs && isMobile && handleViewDetailsClick({ id: orderListItem.id })
      }
    >
      <Box
        borderWidth="thin"
        borderColor="surface.border.gray.muted"
        borderRadius="medium"
        marginBottom="spacing.5"
      >
        <Box backgroundColor="surface.background.gray.moderate" padding="spacing.5" display="flex">
          <Box>
            <Text size="small" marginBottom="spacing.2" color="surface.text.gray.subtle">
              Order Placed
            </Text>
            <Text weight="semibold">
              {moment.unix(orderListItem?.created_at).format('MMMM DD, YYYY')}
            </Text>
          </Box>
          <Box marginX="spacing.9">
            <Text size="small" marginBottom="spacing.2" color="surface.text.gray.subtle">
              Total Amount
            </Text>
            <Text weight="semibold" color="surface.text.gray.subtle">
              <Amount
                value={amount.total}
                suffix="none"
                isAffixSubtle={false}
                type="body"
                size="medium"
                weight="semibold"
              />
            </Text>
          </Box>
          {!isMobile ? (
            <Box marginLeft="auto" display="flex" flexDirection="column" alignItems="end">
              <Text size="small" marginBottom="spacing.2" color="surface.text.gray.subtle">
                Order ID: {id}
              </Text>
              <Badge color={orderStatusMetaData.variant} icon={orderStatusMetaData.icon}>
                {orderStatusMetaData.name}
              </Badge>
            </Box>
          ) : null}
        </Box>
        <Divider />
        <Box padding="spacing.5">
          <Box
            display={{ base: 'block', l: 'flex' }}
            alignItems="center"
            justifyContent="space-between"
            marginBottom="spacing.4"
          >
            <Heading marginBottom="spacing.4" size="small">
              {orderStatusMetaData.statusTitle}{' '}
              {moment.unix(orderStatusMetaData.statusDate).format('MMMM DD, YYYY')}
            </Heading>
            {!isMobile ? (
              <Box>
                <Button
                  isDisabled={shouldDisableCTAs}
                  variant="secondary"
                  onClick={() => handleViewDetailsClick({ id })}
                >
                  View Order Details
                </Button>
              </Box>
            ) : null}
          </Box>
          {items.map((orderItem) => (
            <OrderItem
              key={`${orderItem.code}-${orderItem.period}`}
              orderItem={{
                code: orderItem.code,
                quantity: orderItem.count,
                plan: orderItem.period as ProductPlans,
              }}
              isListItem={true}
            />
          ))}

          <Box>
            {orderStatusMetaData.key === 'ORDER_REJECTED' && orderListItem?.rejection_reasons ? (
              <Alert
                color="negative"
                description={orderListItem.rejection_reasons?.error_description}
                isFullWidth
                isDismissible={false}
              />
            ) : null}
          </Box>
        </Box>
      </Box>
    </div>
  );
};

export default OrderListItem;
