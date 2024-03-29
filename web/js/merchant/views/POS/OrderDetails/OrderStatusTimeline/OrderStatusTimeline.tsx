import React from 'react';
import { Box, CheckIcon, ClockIcon, Divider, SlashIcon, Text } from '@razorpay/blade/components';

import { toTitleCase } from 'common/utils';
import { ORDER_STATUS_META_DATA, ORDER_STATUS_TIMELINE_ITEMS } from 'merchant/views/POS/constants';
import { getOrderStatus } from 'merchant/views/POS/helpers';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { OrderDetailsItem } from 'merchant/views/POS/types';

import { IconContainer, OrderStatusContainer } from './styles';

const StatusIcon = ({ status }): JSX.Element => {
  const renderIcon = () => {
    switch (status) {
      case 'active':
      case 'done':
        return <CheckIcon color="feedback.icon.positive.intense" size="large" />;
      case 'failed':
        return <SlashIcon color="feedback.icon.negative.intense" size="large" />;
      default:
        return <ClockIcon color="feedback.icon.neutral.intense" size="large" />;
    }
  };
  return <IconContainer status={status}>{renderIcon()}</IconContainer>;
};

type OrderStatusTimelineProps = {
  orderDetails: OrderDetailsItem;
};

const OrderStatusTimeline = ({ orderDetails }: OrderStatusTimelineProps): JSX.Element => {
  const { isMobile, matchedBreakpoint } = useBladeBreakpoints();
  const orderStatusMeta = getOrderStatus(orderDetails);
  const orderStatusTimelineMeta = ORDER_STATUS_TIMELINE_ITEMS[orderStatusMeta.key];
  const isMobileOrTablet = isMobile || matchedBreakpoint === 'm';

  return (
    <Box
      display={{ l: 'flex' }}
      marginBottom="spacing.5"
      width="100%"
      testID="order-status-timeline-container"
    >
      {orderStatusTimelineMeta.map(({ descriptionFn, stage, status }, index) => (
        <Box
          key={`${stage.key}-${index}`}
          display="flex"
          flexDirection="column"
          justifyContent="center"
          alignItems={{ base: 'start', l: 'center' }}
          maxWidth="400px"
          width="100%"
        >
          {!isMobileOrTablet ? (
            <OrderStatusContainer
              isActive={status === 'active'}
              isDimmed={stage.key === ORDER_STATUS_META_DATA.REFUND_PENDING.key}
            >
              {toTitleCase(stage.name)}
            </OrderStatusContainer>
          ) : null}
          <Box
            display={{ base: 'block', l: 'grid' }}
            gridTemplateColumns="1fr 0.1fr 1fr"
            width={{ base: 'auto', l: '100%' }}
            alignItems="center"
          >
            {index === 0 ? (
              <Box />
            ) : (
              <Divider
                orientation={isMobileOrTablet ? 'vertical' : 'horizontal'}
                height={isMobileOrTablet ? '25px' : 'auto'}
                left={isMobileOrTablet ? '10px' : '0px'}
                position="relative"
                marginLeft={{ base: '2px', l: '0px' }}
              />
            )}
            <Box position="relative" display="flex" alignItems="center" justifyContent="center">
              <StatusIcon status={status} />
              {isMobileOrTablet ? (
                <Box display="flex" alignItems="center">
                  <OrderStatusContainer
                    isActive={status === 'active'}
                    isMobile={isMobileOrTablet}
                    isDimmed={stage.key === ORDER_STATUS_META_DATA.REFUND_PENDING.key}
                  >
                    {toTitleCase(stage.name)}
                  </OrderStatusContainer>
                  <Box maxWidth="200px">
                    <Text size="small" color="surface.text.gray.muted">
                      {descriptionFn?.(orderDetails)}
                    </Text>
                  </Box>
                </Box>
              ) : null}
            </Box>
            {index === orderStatusTimelineMeta.length - 1 ? (
              <Box flex="0 0 45%" />
            ) : (
              <Divider
                orientation={isMobileOrTablet ? 'vertical' : 'horizontal'}
                height={isMobileOrTablet ? '25px' : 'auto'}
                left={isMobileOrTablet ? '10px' : '0px'}
                position="relative"
                marginLeft={{ base: '2px', l: '0px' }}
              />
            )}
          </Box>
          {!isMobileOrTablet ? (
            <Box height="30px" maxWidth="200px">
              <Text
                size="small"
                marginTop="spacing.4"
                textAlign="center"
                color="surface.text.gray.muted"
              >
                {descriptionFn?.(orderDetails)}
              </Text>
            </Box>
          ) : null}
        </Box>
      ))}
    </Box>
  );
};

export default OrderStatusTimeline;
