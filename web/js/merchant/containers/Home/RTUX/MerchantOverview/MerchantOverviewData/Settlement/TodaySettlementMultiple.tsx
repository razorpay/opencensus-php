import React, { useMemo } from 'react';
import { Amount, Box, ArrowRightIcon, Link, Text } from '@razorpay/blade/components';
import { NavLink } from 'react-router-dom';

import BlueDotIcon from 'assets/rtux/timelineitem-subitem.svg';
import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import {
  IAnalyticsProperties,
  ITodaySettlementData,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { Currency } from 'merchant/views/Transactions/v2/Payments/types';
import { ROUTES } from 'merchant/containers/Home/RTUX/MerchantOverview/constants';

import Dot from './components/Dot';
import Status from './components/Status';
import { getMultipleSettlementBreakup } from './utils';
import { track } from 'merchant/widgets/utils';

const TodaySettlementMultiple: React.FC<
  {
    data: ITodaySettlementData;
    currency: Currency;
  } & IAnalyticsProperties
> = ({ data, currency, analyticsProperties }) => {
  const multipleSettlementsBreakup = useMemo(() => getMultipleSettlementBreakup(data), [data]);

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

  return multipleSettlementsBreakup.length ? (
    <Box display="flex" flexDirection="column" gap="spacing.3">
      {multipleSettlementsBreakup.map(({ amount, status, count }, idx) => (
        <Box
          key={idx}
          display="flex"
          alignItems={{ base: 'start', l: 'center' }}
          flexDirection={{ base: 'column', l: 'row' }}
          gap={{ base: 'spacing.1', l: 'spacing.3' }}
        >
          <Box display="flex" alignItems="center" justifyContent="center" gap="spacing.3">
            <img src={BlueDotIcon} alt="blue dot" />
            <Text type="subtle" weight="bold">
              {count} settlements worth
            </Text>
          </Box>
          <Box
            display="flex"
            alignItems="center"
            justifyContent="center"
            gap={{ base: 'spacing.3', l: 'spacing.2' }}
            marginLeft={{ base: 'spacing.5', l: 'none' }}
          >
            <Amount
              value={i18CurrencyConversionFromMinorUnitToCommonUnit(amount, currency)}
              isAffixSubtle={false}
              size="body-medium-bold"
            />
            <Dot />
            <Status status={status} />
          </Box>
        </Box>
      ))}
      <NavLink to={ROUTES.SETTLEMENT}>
        <Link
          marginTop="spacing.3"
          size="medium"
          icon={ArrowRightIcon}
          iconPosition="right"
          onClick={viewAllSettlementsHandler}
        >
          View all settlements
        </Link>
      </NavLink>
    </Box>
  ) : /* istanbul ignore next */
  null;
};

export default TodaySettlementMultiple;
