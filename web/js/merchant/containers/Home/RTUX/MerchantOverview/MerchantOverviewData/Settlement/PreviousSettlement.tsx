import React from 'react';
import { Box, Text, Amount, Link, ArrowRightIcon } from '@razorpay/blade/components';
import { NavLink } from 'react-router-dom';

import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import {
  TimelineItemIconKeys,
  SettlementStatusBadge,
  IPreviousSettlement,
  IAnalyticsProperties,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { ROUTES } from 'merchant/containers/Home/RTUX/MerchantOverview/constants';

import Dot from './components/Dot';
import { TimelineItem } from './components/Timeline';
import TimelineCardWithAmount from './components/TimelineCardWithAmount';
import { getFormattedDateFromTimestamp } from './utils';
import { track } from 'merchant/widgets/utils';

const PreviousSettlement: React.FC<IPreviousSettlement & IAnalyticsProperties> = ({
  data,
  settlement_currency,
  showOnlyPreviousSettlement,
  analyticsProperties,
}) => {
  const { total_amount, total_count, created_at } = data;

  const viewAllSettlementsHandler = () => {
    const { screen, widgetId, settlementState, ...rest } = analyticsProperties;
    track({
      objectName: 'link',
      actionName: 'clicked' as const,
      screen,
      properties: {
        ...rest,
        widgetId,
        actionBy: widgetId,
        title: settlementState,
        action: ROUTES.SETTLEMENT,
        actionLabel: 'View all settlements',
      },
    });
  };

  return (
    <TimelineItem icon={TimelineItemIconKeys.done}>
      {showOnlyPreviousSettlement ? (
        <TimelineCardWithAmount
          amount={total_amount}
          currency={settlement_currency}
          status={SettlementStatusBadge.processed}
          heading="Last settlement"
          subheading={`Deposited in your bank account on ${getFormattedDateFromTimestamp(
            created_at,
          )}`}
          action={
            <NavLink to={ROUTES.SETTLEMENT}>
              <Link
                size="medium"
                icon={ArrowRightIcon}
                iconPosition="right"
                onClick={viewAllSettlementsHandler}
              >
                View all settlements
              </Link>
            </NavLink>
          }
        />
      ) : (
        <Box
          display="flex"
          flexDirection={{ base: 'column', l: 'row' }}
          gap="spacing.2"
          alignItems={{ base: 'start', l: 'center' }}
        >
          <Text type="subdued" weight="bold">
            <Amount
              value={i18CurrencyConversionFromMinorUnitToCommonUnit(
                total_amount,
                settlement_currency,
              )}
              isAffixSubtle={false}
              size="body-medium-bold"
              currency={settlement_currency}
            />
            &nbsp; deposited across {total_count} settlements on{' '}
            {getFormattedDateFromTimestamp(created_at)}
          </Text>
          <Dot />
          <NavLink to={ROUTES.SETTLEMENT}>
            <Link
              size="medium"
              icon={ArrowRightIcon}
              iconPosition="right"
              onClick={viewAllSettlementsHandler}
            >
              View all settlements
            </Link>
          </NavLink>
        </Box>
      )}
    </TimelineItem>
  );
};

export default PreviousSettlement;
