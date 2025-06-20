import React from 'react';
import { Box, ArrowRightIcon, Link, Text, Amount } from '@razorpay/blade/components';
import {
  isMobileDevice,
  analyticsTrackWithUserInfo,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
} from '@libs/shared-utils';
import {
  ISettlementData,
  UpcomingSettlementKeys,
} from '@federated/dashboards/payments/types/payments';
import { getFormattedDateFromTimestamp } from '@OnboardingExperienceCommons/utils/dateAndTime';

const SettlementStatusInfo = ({ settlementData }: { settlementData?: ISettlementData }) => {
  const isMobile = isMobileDevice();

  const hasUpcomingSettlement =
    settlementData?.upcoming_settlement?.title_key ===
    UpcomingSettlementKeys.UPCOMING_SETL_ON_TRACK;
  const upcomingSettlement = settlementData?.upcoming_settlement;

  const onViewAllSettlements = () => {
    analyticsTrackWithUserInfo({
      objectName: 'FTUX View All Settlements Link',
      actionName: 'Clicked',
      screen: 'home page',
    });
    window.open(`/app/settlements`, '_blank');
  };

  return (
    <Box
      display="flex"
      flexDirection={{ base: 'column', m: 'row' }}
      alignItems={{ base: 'flex-start', m: 'center' }}
      flexWrap="wrap"
      rowGap="spacing.1"
      columnGap="spacing.3"
    >
      {hasUpcomingSettlement ? (
        <Text
          size={isMobile ? 'xsmall' : 'small'}
          weight={isMobile ? 'regular' : 'semibold'}
          color="surface.text.staticWhite.normal"
        >
          {isMobile ? '' : 'Upcoming settlement of '}
          <Amount
            suffix="decimals"
            currency={settlementData?.settlement_currency}
            value={i18CurrencyConversionFromMinorUnitToCommonUnit(
              Number(upcomingSettlement?.settlement_amount),
              settlementData?.settlement_currency,
            )}
            size={isMobile ? 'xsmall' : 'small'}
            weight="semibold"
            type="body"
            isAffixSubtle={false}
            color="surface.text.staticWhite.normal"
          />
          {isMobile ? ' from your ' : ' from a total balance of '}
          <Amount
            suffix="decimals"
            currency={settlementData?.current_balance_currency}
            value={i18CurrencyConversionFromMinorUnitToCommonUnit(
              Number(settlementData?.current_balance),
              settlementData?.current_balance_currency,
            )}
            size={isMobile ? 'xsmall' : 'small'}
            weight="semibold"
            type="body"
            isAffixSubtle={false}
            color="surface.text.staticWhite.normal"
          />
          {isMobile ? ' balance will be settled on ' : ' will be deposited on '}
          {getFormattedDateFromTimestamp(upcomingSettlement?.next_settlement_time || 0)}.
        </Text>
      ) : (
        <Text
          size={isMobile ? 'xsmall' : 'small'}
          weight={isMobile ? 'regular' : 'semibold'}
          color="surface.text.staticWhite.normal"
        >
          No upcoming settlements.
        </Text>
      )}

      <Text
        size={isMobile ? 'xsmall' : 'small'}
        weight={isMobile ? 'regular' : 'semibold'}
        color="surface.text.staticWhite.normal"
      >
        Keep your balance above{' '}
        <Amount
          suffix="none"
          currency="INR"
          value={1}
          size={isMobile ? 'xsmall' : 'small'}
          weight={isMobile ? 'regular' : 'semibold'}
          type="body"
          isAffixSubtle={false}
          color="surface.text.staticWhite.normal"
        />{' '}
        to settle.{' '}
        <Link
          color="white"
          size={isMobile ? 'xsmall' : 'small'}
          icon={ArrowRightIcon}
          iconPosition="right"
          variant="button"
          onClick={onViewAllSettlements}
          data-analytics-name="transaction-timeline-viewall"
        >
          View All
        </Link>
      </Text>
    </Box>
  );
};

export default SettlementStatusInfo;
